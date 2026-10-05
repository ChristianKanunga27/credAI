<?php

namespace App\Http\Controllers;

use App\Enums\InsurancePaymentConfirmationSource;
use App\Models\InsurancePayment;
use App\Services\ClickPesaService;
use App\Services\InsurancePaymentConfirmationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class ClickPesaWebhookController extends Controller
{
    public function __invoke(
        Request $request,
        ClickPesaService $clickPesa,
        InsurancePaymentConfirmationService $confirmations,
    ): JsonResponse {
        $payload = $request->all();
        $data = is_array($payload['data'] ?? null) ? $payload['data'] : $payload;
        $orderReference = $data['orderReference'] ?? null;

        if (! is_string($orderReference) || $orderReference === '') {
            return response()->json(['received' => false, 'message' => 'Missing order reference.'], 422);
        }

        $payment = InsurancePayment::query()
            ->where('reference', $orderReference)
            ->orWhere('clickpesa_control_number', $orderReference)
            ->first();
        if (! $payment) {
            Log::notice('ClickPesa notification references an unknown payment', ['order_reference' => $orderReference]);

            return response()->json(['received' => true]);
        }

        try {
            $verified = $clickPesa->verifyPayment($orderReference);
        } catch (RuntimeException $exception) {
            Log::warning('ClickPesa payment notification could not be verified', [
                'payment_id' => $payment->id,
                'message' => $exception->getMessage(),
            ]);

            return response()->json(['received' => false, 'message' => 'Payment status verification is temporarily unavailable.'], 503);
        }

        $expectedOrderReference = $payment->clickpesa_control_number ?: $payment->reference;
        if ($verified['order_reference'] !== $expectedOrderReference) {
            Log::error('ClickPesa order reference mismatch', ['payment_id' => $payment->id]);

            return response()->json(['received' => true]);
        }

        if (! in_array($verified['status'], ['SUCCESS', 'SETTLED', 'FAILED'], true)) {
            return response()->json(['received' => false, 'message' => 'ClickPesa has not reported a final payment status yet.'], 503);
        }

        if (in_array($verified['status'], ['SUCCESS', 'SETTLED'], true)) {
            $amountMatches = number_format((float) $verified['amount'], 2, '.', '')
                === number_format((float) $payment->amount, 2, '.', '');
            if (! $amountMatches || $verified['currency'] !== strtoupper($payment->currency) || $verified['reference'] === '') {
                Log::error('ClickPesa payment amount, currency, or receipt did not match the local order', [
                    'payment_id' => $payment->id,
                    'verified_currency' => $verified['currency'],
                ]);

                return response()->json(['received' => true]);
            }

            $confirmations->confirm(
                $payment,
                $verified['reference'],
                InsurancePaymentConfirmationSource::Gateway,
            );
        } elseif ($verified['status'] === 'FAILED') {
            DB::transaction(function () use ($payment, $verified): void {
                $lockedPayment = InsurancePayment::query()->whereKey($payment->id)->lockForUpdate()->firstOrFail();
                if (in_array($lockedPayment->status, ['pending', 'processing'], true)) {
                    $lockedPayment->forceFill(['status' => 'failed'])->save();
                    DB::table('audit_events')->insert([
                        'actor_id' => null,
                        'event' => 'insurance_payment.failed',
                        'auditable_type' => InsurancePayment::class,
                        'auditable_id' => $lockedPayment->id,
                        'metadata' => json_encode([
                            'reference' => $lockedPayment->reference,
                            'confirmation_source' => 'clickpesa',
                            'message' => $verified['message'],
                        ], JSON_THROW_ON_ERROR),
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            });
        }

        return response()->json(['received' => true]);
    }
}
