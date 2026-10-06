<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

// Set up test environment
$app['config']->set('database.default', 'mysql');
$app['config']->set('database.connections.mysql.host', '127.0.0.1');
$app['config']->set('database.connections.mysql.port', '3308');
$app['config']->set('database.connections.mysql.database', 'credAI');
$app['config']->set('database.connections.mysql.username', 'root');
$app['config']->set('database.connections.mysql.password', '');

$app['config']->set('services.clickpesa.client_id', 'test-client');
$app['config']->set('services.clickpesa.api_key', 'test-api-key');
$app['config']->set('services.clickpesa.api_url', 'https://api.clickpesa.com');
$app['config']->set('services.clickpesa.timeout', 15);

use Illuminate\Support\Facades\Http;
use Illuminate\Http\Client\Request as HttpClientRequest;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\InsuranceProfile;
use App\Models\InsurancePayment;

Http::fake([
    '*api.clickpesa.com/third-parties/generate-token' => Http::response(['token' => 'Bearer test-token']),
    '*api.clickpesa.com/third-parties/billpay/create-customer-control-number' => fn (HttpClientRequest $request) => Http::response([
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

$request = Request::create(route('insurance.payments.store'), 'POST', [
    'phone' => $customer->phone,
    'collection_method' => 'control_number',
]);
$request->setUserResolver(fn () => $customer);

$response = $app->handle($request);

echo "Status: " . $response->getStatusCode() . "\n";
echo "Redirect: " . ($response->isRedirect() ? $response->getTargetUrl() : 'none') . "\n";

$payment = InsurancePayment::query()->firstOrFail();
echo "Payment collection_method: " . $payment->collection_method . "\n";
echo "Payment status: " . $payment->status . "\n";
echo "Payment control_number: " . $payment->clickpesa_control_number . "\n";

echo "Sent requests count: " . count(Http::sent()) . "\n";
foreach (Http::sent() as $i => $req) {
    echo "Request $i: " . $req->url() . "\n";
}

try {
    Http::assertSent(fn (HttpClientRequest $request): bool => str_ends_with($request->url(), '/third-parties/billpay/create-customer-control-number')
        && $request['billAmount'] === 50400
        && $request['billPaymentMode'] === 'EXACT'
        && $request['customerPhone'] === '255712345678');
    echo "Assertion passed!\n";
} catch (\Throwable $e) {
    echo "Assertion failed: " . $e->getMessage() . "\n";
}
