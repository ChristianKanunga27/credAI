<?php

use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

uses(TestCase::class);

test('chat endpoint returns a response with the requested page context', function () {
    config()->set('services.ai.provider', 'openai');
    config()->set('services.ai.api_url', 'https://api.openai.com/v1');
    config()->set('services.ai.api_key', 'test-key');
    config()->set('services.ai.model', 'gpt-5-mini');

    Http::preventStrayRequests();
    Http::fake([
        'api.openai.com/v1/chat/completions' => Http::response([
            'choices' => [['message' => ['content' => 'Review the options before applying.']]],
        ]),
    ]);

    $response = $this->postJson(route('ai.chat'), [
        'message' => 'How do I compare plans?',
        'page' => 'quote',
    ]);

    $response->assertOk()->assertExactJson([
        'status' => 'ready',
        'response' => 'Review the options before applying.',
    ]);

    Http::assertSent(fn (Request $request): bool => $request->url() === 'https://api.openai.com/v1/chat/completions'
        && $request->data()['model'] === 'gpt-5-mini'
        && str_contains($request->data()['messages'][0]['content'], 'insurance quote and payment options')
        && str_contains($request->data()['messages'][0]['content'], 'visitor is a guest')
    );
});
