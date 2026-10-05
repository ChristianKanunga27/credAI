<?php

namespace App\Http\Controllers;

use App\Exceptions\ClickPesaPaymentException;
use App\Models\InsurancePayment;
use App\Models\InsuranceProvider;
use App\Models\ProviderService;
use App\Models\User;
use App\Services\ClickPesaService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class ProviderServiceController extends Controller
{
    public function manage(Request $request): View
    {
        $provider = InsuranceProvider::query()->whereBelongsTo($request->user())->first();
        $services = $provider
            ? ProviderService::query()->whereBelongsTo($provider)->latest()->get()
            : collect();

        return view('provider-services.manage', compact('provider', 'services'));
    }

    public function index(Request $request): View
    {
        $services = ProviderService::query()
            ->with('provider:id,organization_name,provider_type')
            ->where('is_active', true)
            ->whereHas('provider', fn (Builder $provider) => $provider->where('status', 'approved'))
            ->latest()
            ->paginate(20);

        return view('provider-services.index', [
            'services' => $services,
            'phone' => $request->user()->insuranceProfile?->phone ?? $request->user()->phone,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $provider = $this->approvedProvider($request);
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'description' => ['nullable', 'string', 'max:1000'],
            'price' => ['required', 'numeric', 'min:1', 'max:100000000'],
        ]);

        $service = ProviderService::create([
            ...$validated,
            'insurance_provider_id' => $provider->id,
            'is_active' => true,
        ]);
        $this->recordServiceActivity($request, $service, 'provider_service.created');

        return back()->with('status', __('Service published in the customer catalog.'));
    }

    public function update(Request $request, ProviderService $service): RedirectResponse
    {
        $provider = $this->approvedProvider($request);
        abort_unless($service->insurance_provider_id === $provider->id, 404);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'description' => ['nullable', 'string', 'max:1000'],
            'price' => ['required', 'numeric', 'min:1', 'max:100000000'],
            'is_active' => ['required', 'boolean'],
        ]);

        $service->update($validated);
        $this->recordServiceActivity($request, $service, 'provider_service.updated');

        return back()->with('status', __('Service details updated.'));
    }

    public function pay(
        Request $request,
        ProviderService $service,
        ClickPesaService $clickPesa,
    ): RedirectResponse {
        if (! $clickPesa->isConfigured()) {
            return back()->with('error', __('ClickPesa is not configured yet. Add CLICKPESA_CLIENT_ID and CLICKPESA_API_KEY to the server environment.'));
        }

        if (! $request->exists('collection_method')) {
            $request->merge(['collection_method' => 'ussd']);
        }

        $validated = $request->validate([
            'phone' => ['required', 'string', 'max:30'],
            'collection_method' => ['required', 'in:ussd,control_number'],
        ]);
        $payment = DB::transaction(function () use ($request, $validated, $service): InsurancePayment {
            $user = User::query()->whereKey($request->user()->id)->lockForUpdate()->firstOrFail();

            $lockedService = ProviderService::query()
                ->whereKey($service->getKey())
                ->lockForUpdate()
                ->firstOrFail();
            abort_unless(
                $lockedService->is_active && $lockedService->provider()->where('status', 'approved')->exists(),
                404,
            );

            if (InsurancePayment::query()->whereBelongsTo($user)
                ->where('provider_service_id', $lockedService->id)
                ->whereIn('status', ['pending', 'processing'])
                ->exists()) {
                throw ValidationException::withMessages([
                    'phone' => __('You already have a checkout awaiting payment for this service.'),
                ]);
            }

            $payment = InsurancePayment::create([
                'user_id' => $user->id,
                'provider_service_id' => $lockedService->id,
                'payment_type' => 'provider_service',
                'collection_method' => $validated['collection_method'],
                'reference' => Str::upper(Str::random(20)),
                'phone' => $validated['phone'],
                'amount' => $lockedService->price,
                'currency' => 'TZS',
                'status' => 'pending',
            ]);

            DB::table('audit_events')->insert([
                'actor_id' => $request->user()->id,
                'event' => 'provider_service.payment_requested',
                'auditable_type' => InsurancePayment::class,
                'auditable_id' => $payment->id,
                'metadata' => json_encode([
                    'reference' => $payment->reference,
                    'service_id' => $lockedService->id,
                    'provider_id' => $lockedService->insurance_provider_id,
                    'amount' => $payment->amount,
                    'currency' => $payment->currency,
                ], JSON_THROW_ON_ERROR),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            return $payment;
        });

        try {
            if ($payment->collection_method === 'control_number') {
                $result = $clickPesa->createControlNumber($payment);
                $payment->forceFill([
                    'clickpesa_control_number' => $result['control_number'],
                    'status' => 'pending',
                ])->save();

                return back()->with('status', __('Pay TZS :amount for :service using mobile money control number :number.', [
                    'amount' => number_format((float) $payment->amount, 2),
                    'service' => $service->name,
                    'number' => $result['control_number'],
                ]));
            }

            $result = $clickPesa->initiateMobileMoneyPayment($payment);
            $payment->forceFill([
                'status' => 'processing',
                'provider_reference' => $result['provider_reference'] ?: null,
            ])->save();
        } catch (ClickPesaPaymentException $exception) {
            $payment->forceFill(['status' => 'failed'])->save();

            if ($payment->collection_method === 'ussd' && $exception->shouldFallbackToControlNumber()) {
                try {
                    $result = $clickPesa->createControlNumber($payment);
                    $payment->forceFill([
                        'collection_method' => 'control_number',
                        'clickpesa_control_number' => $result['control_number'],
                        'status' => 'pending',
                    ])->save();

                    return redirect()->route('dashboard')->with('status', __('USSD is not active on this ClickPesa network. Pay for :service from your mobile-money menu using control number :number.', [
                        'service' => $service->name,
                        'number' => $result['control_number'],
                    ]));
                } catch (\Throwable $fallbackException) {
                    $payment->forceFill(['status' => 'failed'])->save();
                    if (! $fallbackException instanceof ClickPesaPaymentException) {
                        report($fallbackException);
                    }

                    return back()->with('error', $fallbackException instanceof ClickPesaPaymentException
                        ? $fallbackException->customerMessage()
                        : __('USSD and control-number payment could not be started. Please check ClickPesa BillPay settings or contact support.'));
                }
            }

            return back()->with('error', $exception->customerMessage());
        } catch (\Throwable $exception) {
            $payment->forceFill(['status' => 'failed'])->save();
            report($exception);

            return back()->with('error', __('ClickPesa could not start the payment. Please try the control-number option or contact support.'));
        }

        return redirect()->route('dashboard')->with('status', __('Approve the TZS :amount payment request on your phone to complete it.', [
            'amount' => number_format((float) $payment->amount, 2),
        ]));
    }

    private function approvedProvider(Request $request): InsuranceProvider
    {
        return InsuranceProvider::query()
            ->whereBelongsTo($request->user())
            ->where('status', 'approved')
            ->firstOrFail();
    }

    private function recordServiceActivity(Request $request, ProviderService $service, string $event): void
    {
        DB::table('audit_events')->insert([
            'actor_id' => $request->user()->id,
            'event' => $event,
            'auditable_type' => ProviderService::class,
            'auditable_id' => $service->id,
            'metadata' => json_encode([
                'provider_id' => $service->insurance_provider_id,
                'name' => $service->name,
                'price' => $service->price,
                'is_active' => $service->is_active,
            ], JSON_THROW_ON_ERROR),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
