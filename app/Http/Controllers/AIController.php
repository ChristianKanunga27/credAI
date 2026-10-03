<?php

namespace App\Http\Controllers;

use App\Services\OpenAIService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AIController extends Controller
{
    public function __construct(private readonly OpenAIService $openAI) {}

    public function index(): View
    {
        return view('ai.chat');
    }

    public function chat(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'message' => ['required', 'string', 'max:5000'],
            'history' => ['sometimes', 'array', 'max:8'],
            'history.*' => ['array:role,content'],
            'history.*.role' => ['required', 'in:user,assistant'],
            'history.*.content' => ['required', 'string', 'max:2000'],
            'page' => ['required', 'in:public,auth,quote,dashboard,provider,admin,profile,general'],
        ]);

        $page = match ($validated['page']) {
            'admin' => 'admin dashboard',
            'provider' => 'insurance provider workspace',
            'quote' => 'insurance quote and payment options',
            'dashboard' => 'customer dashboard',
            'auth' => 'account access',
            'profile' => 'profile settings',
            'public' => 'public CredAI page',
            default => 'general CredAI page',
        };

        return response()->json($this->openAI->chat(
            $validated['message'],
            $validated['history'] ?? [],
            $page,
            $request->user()?->role ?? 'guest',
            app()->getLocale()
        ));
    }
}
