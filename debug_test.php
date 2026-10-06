<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\User;
use App\Models\InsuranceProfile;
use App\Models\InsurancePayment;
use Illuminate\Support\Facades\Http;
use Illuminate\Http\Client\Request;

Http::fake([
    '*api.clickpesa.com/third-parties/generate-token' => Http::response(['token' => 'Bearer test-token']),
    '*api.clickpesa.com/third-parties/billpay/create-customer-control-number' => fn (Request $request) => Http::response([
        'billPayNumber' => $request['billReference'],
    ]),
]);

$customer = User::factory()->create(['role' => 'individual', 'phone' => '+255712345678']);
InsuranceProfile::create([
    'user_id' => $customer->id,
    'full_name' => $customer->name,
    'phone' => $customer->phone,
    'monthly_premium' => 3500,
    'coverage_amount' => 700000,
]);

$response = (new \Illuminate\Foundation\Testing\Concerns\InteractsWithContainer())->post(
    route('insurance.payments.store'),
    [
        'phone' => $customer->phone,
        'collection_method' => 'control_number',
    ]
);

echo "Status: " . $response->getStatusCode() . "\n";
echo "Redirect: " . ($response->isRedirect() ? $response->getTargetUrl() : 'none') . "\n";

$payment = InsurancePayment::query()->firstOrFail();
echo "Payment collection_method: " . $payment->collection_method . "\n";
echo "Payment status: " . $payment->status . "\n";
echo "Payment control_number: " . $payment->clickpesa_control_number . "\n";

try {
    Http::assertSent(fn (Request $request): bool => str_ends_with($request->url(), '/third-parties/billpay/create-customer-control-number')
        && $request['billAmount'] === 50400
        && $request['billPaymentMode'] === 'EXACT'
        && $request['customerPhone'] === '255712345678');
    echo "Assertion passed!\n";
} catch (\Throwable $e) {
    echo "Assertion failed: " . $e->getMessage() . "\n";
    
    $sent = Http::sent();
    echo "Sent requests count: " . count($sent) . "\n";
    foreach ($sent as $i => $req) {
        echo "Request $i: " . $req->url() . "\n";
    }
}
