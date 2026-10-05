<?php

namespace App\Http\Controllers;

use App\Models\InsuranceClaim;
use App\Models\InsurancePayment;
use App\Models\InsurancePolicy;
use App\Models\InsuranceProvider;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class ProviderDashboardController extends Controller
{
    public function index(Request $request): View
    {
        $provider = InsuranceProvider::whereBelongsTo($request->user())->first();
        $policies = $provider
            ? InsurancePolicy::with('user:id,name')->where('insurance_provider_id', $provider->id)->latest()->limit(20)->get()
            : collect();
        $claims = $provider
            ? InsuranceClaim::with(['user:id,name', 'policy:id,policy_number'])
                ->whereHas('policy', fn ($query) => $query->where('insurance_provider_id', $provider->id))
                ->latest()
                ->limit(20)
                ->get()
            : collect();
        $servicePayments = $provider
            ? InsurancePayment::query()
                ->with(['user:id,name', 'providerService:id,name'])
                ->whereHas('providerService', fn ($query) => $query->where('insurance_provider_id', $provider->id))
                ->latest()
                ->limit(20)
                ->get()
            : collect();

        return view('provider.dashboard', compact('provider', 'policies', 'claims', 'servicePayments'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'organization_name' => ['required', 'string', 'max:255'],
            'license_number' => ['required', 'string', 'max:255'],
        ]);

        $provider = InsuranceProvider::firstOrNew(['user_id' => Auth::id()]);
        $requiresApproval = ! $provider->exists
            || $provider->organization_name !== $validated['organization_name']
            || $provider->license_number !== $validated['license_number'];

        $provider->fill($validated);
        $provider->provider_type = Auth::user()->role === 'hospital' ? 'hospital' : 'insurer';

        if ($requiresApproval) {
            $provider->status = 'pending';
        }

        $provider->save();

        DB::table('audit_events')->insert([
            'actor_id' => Auth::id(),
            'event' => 'insurance_provider.profile_submitted',
            'auditable_type' => InsuranceProvider::class,
            'auditable_id' => $provider->id,
            'metadata' => json_encode(['status' => $provider->status], JSON_THROW_ON_ERROR),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return redirect()->route('provider.dashboard')->with('status', __('Provider details submitted for review.'));
    }
}
