<?php

namespace App\Services;

use App\Exceptions\FastLipaPaymentException;
use App\Models\InsurancePayment;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class FastLipaService
{
    public function isConfigured(): bool
    {
        return filled(config('services.fastlipa.api_key'))
            && filled(config('services.fastlipa.api_url'));
    }

    /** @return array{status:string, provider_reference:string} */
    public function initiateMobileMoneyPayment(InsurancePayment $payment): array
    {
        $payload = [
            'amount' => number_format((float) $payment->amount, 2, '.', ''),
            'currency' => $payment->currency,
            'reference' => $payment->reference,
            'phone_number' => $this->normalizePhone($payment->phone),
            'callback_url' => route('fastlipa.webhook'),
        ];

        $url = $this->url('v1/payments/mobile-money');
        $response = $this->send(fn (PendingRequest $request): Response => $request->post($url, $payload));

        if (! $response->successful() || ! in_array(strtoupper((string) $response->json('status')), ['PENDING', 'PROCESSING', 'SUCCESS'], true)) {
            Log::warning('FastLipa mobile-money request was rejected', [
                'payment_id' => $payment->id,
                'http_status' => $response->status(),
                'provider_message' => $response->json('message'),
                'provider_status' => $response->json('status'),
            ]);

            throw new FastLipaPaymentException(
                'FastLipa could not send the mobile-money payment prompt.',
                $response->status(),
                is_string($response->json('message')) ? $response->json('message') : null,
            );
        }

        return [
            'status' => strtoupper((string) $response->json('status')),
            'provider_reference' => (string) ($response->json('transaction_id') ?? $response->json('id') ?? ''),
        ];
    }

    /** @return array{control_number:string} */
    public function createControlNumber(InsurancePayment $payment): array
    {
        $user = $payment->user;
        $profile = $payment->profile;
        $billReference = (string) random_int(10000000000000, 99999999999999);

        $payload = [
            'amount' => (float) number_format((float) $payment->amount, 2, '.', ''),
            'currency' => $payment->currency,
            'reference' => $billReference,
            'description' => $payment->provider_service_id
                ? 'CredHealth provider service payment'
                : 'CredHealth annual insurance premium',
            'customer_name' => mb_substr((string) ($profile?->full_name ?: $user?->name ?: 'CredHealth customer'), 0, 150),
            'customer_email' => (string) ($user?->email ?? ''),
            'customer_phone' => $this->normalizePhone($payment->phone),
            'callback_url' => route('fastlipa.webhook'),
        ];

        $url = $this->url('v1/payments/control-number');
        $response = $this->send(fn (PendingRequest $request): Response => $request->post($url, $payload));

        $controlNumber = $response->json('control_number') ?? $response->json('bill_number') ?? '';

        if (! $response->successful() || ! is_string($controlNumber) || trim($controlNumber) === '') {
            Log::warning('FastLipa control-number request was rejected', [
                'payment_id' => $payment->id,
                'http_status' => $response->status(),
                'provider_message' => $response->json('message'),
            ]);

            throw new FastLipaPaymentException(
                'FastLipa could not create a mobile-money control number.',
                $response->status(),
                is_string($response->json('message')) ? $response->json('message') : null,
            );
        }

        return ['control_number' => trim($controlNumber)];
    }

    public function deactivateControlNumber(InsurancePayment $payment): void
    {
        $controlNumber = trim((string) $payment->fastlipa_control_number);
        if ($controlNumber === '') {
            throw new FastLipaPaymentException('FastLipa control number is missing.');
        }

        $url = $this->url('v1/payments/control-number/'.rawurlencode($controlNumber));
        $response = $this->send(fn (PendingRequest $request): Response => $request->patch($url, [
            'status' => 'CANCELLED',
        ]));

        if (! $response->successful()
            || $response->json('control_number') !== $controlNumber
            || strtoupper((string) $response->json('status')) !== 'CANCELLED') {
            Log::warning('FastLipa control-number deactivation was rejected', [
                'payment_id' => $payment->id,
                'http_status' => $response->status(),
                'provider_message' => $response->json('message'),
            ]);

            throw new FastLipaPaymentException(
                'FastLipa could not deactivate the earlier control number.',
                $response->status(),
                is_string($response->json('message')) ? $response->json('message') : null,
            );
        }
    }

    /**
     * Verify an order directly against FastLipa before changing its local payment status.
     *
     * @return array{status:string, reference:string, order_reference:string, amount:string, currency:string, message:?string}
     */
    public function verifyPayment(string $orderReference): array
    {
        $statusUrl = $this->url('v1/payments/'.rawurlencode($orderReference));
        $response = $this->send(fn (PendingRequest $request): Response => $request->get($statusUrl));

        if (! $response->successful()) {
            throw new RuntimeException('FastLipa payment status could not be verified.');
        }

        $payment = $response->json();
        if (is_array($payment) && array_is_list($payment)) {
            $payment = $payment[0] ?? null;
        }
        if (! is_array($payment)) {
            throw new RuntimeException('FastLipa returned an invalid payment status response.');
        }

        return [
            'status' => strtoupper((string) ($payment['status'] ?? '')),
            'reference' => (string) ($payment['transaction_id'] ?? $payment['reference'] ?? $payment['id'] ?? ''),
            'order_reference' => (string) ($payment['reference'] ?? ''),
            'amount' => (string) ($payment['amount'] ?? ''),
            'currency' => strtoupper((string) ($payment['currency'] ?? '')),
            'message' => isset($payment['message']) ? (string) $payment['message'] : null,
        ];
    }

    private function send(callable $send): Response
    {
        return $send($this->request());
    }

    private function request(): PendingRequest
    {
        if (! $this->isConfigured()) {
            throw new RuntimeException('FastLipa is not configured. Add the FastLipa credentials to the server environment.');
        }

        return Http::acceptJson()
            ->asJson()
            ->timeout((int) config('services.fastlipa.timeout', 15))
            ->withHeaders([
                'Authorization' => 'Bearer '.config('services.fastlipa.api_key'),
            ]);
    }

    private function url(string $path): string
    {
        return rtrim((string) config('services.fastlipa.api_url'), '/').'/'.ltrim($path, '/');
    }

    private function normalizePhone(string $phone): string
    {
        $digits = preg_replace('/\D+/', '', $phone) ?? '';
        if (str_starts_with($digits, '0')) {
            return '255'.substr($digits, 1);
        }

        if (str_starts_with($digits, '255')) {
            return $digits;
        }

        if (strlen($digits) === 9 && preg_match('/^[67]/', $digits)) {
            return '255'.$digits;
        }

        return $digits;
    }
}