<?php

use App\Services\OpenAIService;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

uses(TestCase::class);

beforeEach(function () {
    config()->set('services.ai.provider', 'openai');
    config()->set('services.ai.api_url', 'https://api.openai.com/v1');
    config()->set('services.ai.api_key', 'test-key');
    config()->set('services.ai.model', 'gpt-5-mini');
    config()->set('services.ai.timeout', 5);
});

test('assistant sends a project-aware prompt to the configured model', function () {
    Http::fake([
        'api.openai.com/v1/chat/completions' => Http::response([
            'choices' => [['message' => ['content' => 'Compare the cover options before applying.']]],
        ]),
    ]);

    $result = app(OpenAIService::class)->chat(
        'How do I compare my options?',
        [],
        'insurance quote and payment options',
        'individual',
        'sw'
    );

    expect($result)->toBe([
        'status' => 'ready',
        'response' => 'Compare the cover options before applying.',
    ]);

    Http::assertSent(fn (Request $request): bool => $request->url() === 'https://api.openai.com/v1/chat/completions'
        && $request->hasHeader('Authorization', 'Bearer test-key')
        && $request->data()['model'] === 'gpt-5-mini'
        && str_contains($request->data()['messages'][0]['content'], 'Swahili')
        && str_contains($request->data()['messages'][0]['content'], 'insurance quote and payment options')
    );
});

test('assistant reports missing credentials without making an HTTP request', function () {
    config()->set('services.ai.api_key', null);
    Http::preventStrayRequests();

    $result = app(OpenAIService::class)->chat('How do I apply?', [], 'dashboard', 'individual', 'en');

    expect($result['status'])->toBe('not_configured');
    expect($result['response'])->toContain('not configured');
});

test('assistant handles provider errors without exposing provider details', function () {
    Http::fake([
        'api.openai.com/v1/chat/completions' => Http::response(['error' => 'private provider detail'], 503),
    ]);

    $result = app(OpenAIService::class)->chat('How do claims work?', [], 'admin dashboard', 'admin', 'en');

    expect($result['status'])->toBe('unavailable');
    expect($result['response'])->not->toContain('private provider detail');
});

test('assistant explains when the provider account has no credits', function () {
    Http::preventStrayRequests();
    Http::fake([
        'api.openai.com/v1/chat/completions' => Http::response([
            'error' => [
                'message' => 'You have no credits remaining.',
                'type' => 'insufficient_quota',
                'code' => 'insufficient_quota',
            ],
        ], 429),
    ]);

    $result = app(OpenAIService::class)->chat('How do claims work?', [], 'dashboard', 'individual', 'en');

    expect($result['status'])->toBe('unavailable');
    expect($result['response'])->toBe('AI support is paused because the service account has no credits. Please contact support.');
});

test('quote explanation sends only non-identifying recommendation attributes', function () {
    Http::fake([
        'api.openai.com/v1/chat/completions' => Http::response([
            'choices' => [['message' => ['content' => 'This plan matches your selected protection goal.']]],
        ]),
    ]);

    $summary = app(OpenAIService::class)->explainQuote([
        'goal_label' => 'Family protection',
        'risk_level' => 'moderate',
        'recommended_plan' => 'Starter Cover',
        'customer' => 'A private customer name',
        'sim_balance' => 250000,
        'monthly_premium' => 3500,
        'coverage_amount' => 700000,
    ], 'en');

    expect($summary)->toBe('This plan matches your selected protection goal.');

    Http::assertSent(fn (Request $request): bool => ! str_contains(json_encode($request->data()['messages'], JSON_THROW_ON_ERROR), 'A private customer name')
        && ! str_contains(json_encode($request->data()['messages'], JSON_THROW_ON_ERROR), '250000')
        && str_contains(json_encode($request->data()['messages'], JSON_THROW_ON_ERROR), 'Family protection')
    );
});
