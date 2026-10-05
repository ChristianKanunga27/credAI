<?php

namespace App\Services;

use App\Exceptions\ClickPesaPaymentException;
use App\Models\InsurancePayment;
use App\Models\User;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class ClickPesaService
{
    public function isConfigured(): bool
    {
        return filled(config('services.clickpesa.client_id'))
            && filled(config('services.clickpesa.api_key'))
            && filled(config('services.clickpesa.api_url'));
    }

    /** @return array{status:string, provider_reference:string} */
    public function initiateMobileMoneyPayment(InsurancePayment $payment): array
    {
        $payload = [
            'amount' => number_format((float) $payment->amount, 2, '.', ''),
            'currency' => $payment->currency,
            'orderReference' => $payment->reference,
            'phoneNumber' => $this->normalizePhone($payment->phone),
        ];

        $previewUrl = $this->url('third-parties/payments/preview-ussd-push-request');
        $preview = $this->send(fn (PendingRequest $request): Response => $request->post($previewUrl, [
            ...$payload,
            'fetchSenderDetails' => false,
        ]));
        $activeMethods = $preview->json('activeMethods');
        $hasAvailableMethod = is_array($activeMethods)
            && collect($activeMethods)->contains(fn ($method): bool => is_array($method)
                && strtoupper((string) ($method['status'] ?? '')) === 'AVAILABLE');
        if (! $preview->successful() || ! $hasAvailableMethod) {
            Log::warning('ClickPesa mobile-money payment preview was rejected', [
                'payment_id' => $payment->id,
                'http_status' => $preview->status(),
                'provider_message' => $preview->json('message'),
            ]);
            throw new ClickPesaPaymentException(
                'ClickPesa could not validate the mobile-money number or amount.',
                $preview->status(),
                is_string($preview->json('message')) ? $preview->json('message') : null,
            );
        }

        $initiateUrl = $this->url('third-parties/payments/initiate-ussd-push-request');
        $response = $this->send(fn (PendingRequest $request): Response => $request->post($initiateUrl, $payload));
        if (! $response->successful() || $response->json('orderReference') !== $payment->reference
            || ! in_array(strtoupper((string) $response->json('status')), ['PROCESSING', 'SUCCESS', 'SETTLED'], true)) {
            Log::warning('ClickPesa mobile-money request was rejected', [
                'payment_id' => $payment->id,
                'http_status' => $response->status(),
                'provider_message' => $response->json('message'),
                'provider_status' => $response->json('status'),
                'response_fields' => is_array($response->json()) ? array_keys($response->json()) : [],
                'order_reference_matches' => $response->json('orderReference') === $payment->reference,
            ]);
            throw new ClickPesaPaymentException(
                'ClickPesa could not send the mobile-money payment prompt.',
                $response->status(),
                is_string($response->json('message')) ? $response->json('message') : null,
            );
        }

        return [
            'status' => strtoupper((string) $response->json('status')),
            'provider_reference' => (string) ($response->json('id') ?? ''),
        ];
    }

    /** @return array{control_number:string} */
    public function createControlNumber(InsurancePayment $payment): array
    {
        $user = $payment->user;
        $profile = $payment->profile;
        $billReference = (string) random_int(10000000000000, 99999999999999);
        $payload = [
            'billDescription' => $payment->provider_service_id
                ? 'CredHealth provider service payment'
                : 'CredHealth annual insurance premium',
            'billPaymentMode' => 'EXACT',
            'billAmount' => (float) number_format((float) $payment->amount, 2, '.', ''),
            'billReference' => $billReference,
            'customerName' => mb_substr((string) ($profile?->full_name ?: $user?->name ?: 'CredHealth customer'), 0, 150),
            'customerEmail' => (string) ($user?->email ?? ''),
            'customerPhone' => $this->normalizePhone($payment->phone),
        ];

        $url = $this->url('third-parties/billpay/create-customer-control-number');
        $response = $this->send(fn (PendingRequest $request): Response => $request->post($url, $payload));
        $controlNumber = $response->json('billPayNumber');

        if (! $response->successful() || ! is_string($controlNumber) || trim($controlNumber) === '') {
            Log::warning('ClickPesa control-number request was rejected', [
                'payment_id' => $payment->id,
                'http_status' => $response->status(),
                'provider_message' => $response->json('message'),
            ]);

            throw new ClickPesaPaymentException(
                'ClickPesa could not create a mobile-money control number.',
                $response->status(),
                is_string($response->json('message')) ? $response->json('message') : null,
            );
        }

        if (trim($controlNumber) !== $billReference) {
            Log::error('ClickPesa control-number reference did not match the requested bill reference', [
                'payment_id' => $payment->id,
            ]);

            throw new ClickPesaPaymentException('ClickPesa returned an unexpected control number.');
        }

        return ['control_number' => trim($controlNumber)];
    }

    /**
     * Verify an order directly against ClickPesa before changing its local payment status.
     *
     * @return array{status:string, reference:string, order_reference:string, amount:string, currency:string, message:?string}
     */
    public function verifyPayment(string $orderReference): array
    {
        $statusUrl = $this->url('third-parties/payments/'.rawurlencode($orderReference));
        $response = $this->send(fn (PendingRequest $request): Response => $request->get($statusUrl));
        if (! $response->successful()) {
            throw new RuntimeException('ClickPesa payment status could not be verified.');
        }

        $payment = $response->json();
        if (is_array($payment) && array_is_list($payment)) {
            $payment = $payment[0] ?? null;
        }
        if (! is_array($payment)) {
            throw new RuntimeException('ClickPesa returned an invalid payment status response.');
        }

        return [
            'status' => strtoupper((string) ($payment['status'] ?? '')),
            'reference' => (string) ($payment['paymentReference'] ?? $payment['id'] ?? ''),
            'order_reference' => (string) ($payment['orderReference'] ?? ''),
            'amount' => (string) ($payment['collectedAmount'] ?? ''),
            'currency' => strtoupper((string) ($payment['collectedCurrency'] ?? '')),
            'message' => isset($payment['message']) ? (string) $payment['message'] : null,
        ];
    }

    private function send(callable $send): Response
    {
        $response = $send($this->request());
        if ($response->status() !== 401) {
            return $response;
        }

        Cache::forget('clickpesa.token.'.hash('sha256', (string) config('services.clickpesa.client_id')));

        return $send($this->request());
    }

    private function request(): PendingRequest
    {
        if (! $this->isConfigured()) {
            throw new RuntimeException('ClickPesa is not configured. Add the ClickPesa credentials to the server environment.');
        }

        return Http::acceptJson()
            ->asJson()
            ->timeout((int) config('services.clickpesa.timeout', 15))
            ->withToken($this->token());
    }

    private function token(): string
    {
        $clientId = (string) config('services.clickpesa.client_id');
        $cacheKey = 'clickpesa.token.'.hash('sha256', $clientId);
        $cachedToken = Cache::get($cacheKey);
        if (is_string($cachedToken) && $cachedToken !== '') {
            return $cachedToken;
        }

        $response = Http::acceptJson()
            ->timeout((int) config('services.clickpesa.timeout', 15))
            ->withHeaders([
                'client-id' => $clientId,
                'api-key' => (string) config('services.clickpesa.api_key'),
            ])
            ->post($this->url('third-parties/generate-token'));

        $token = $response->json('token');
        if (! $response->successful() || ! is_string($token) || trim($token) === '') {
            Log::warning('ClickPesa authorization failed', ['http_status' => $response->status()]);
            throw new RuntimeException('ClickPesa authorization failed. Check the Client ID and API key.');
        }

        $token = preg_replace('/^Bearer\s+/i', '', trim($token)) ?: trim($token);
        Cache::put($cacheKey, $token, now()->addSeconds(max(60, (int) config('services.clickpesa.token_cache_seconds', 3500))));

        return $token;
    }

    private function url(string $path): string
    {
        return rtrim((string) config('services.clickpesa.api_url'), '/').'/'.ltrim($path, '/');
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
