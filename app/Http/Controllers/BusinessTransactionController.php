<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class BusinessTransactionController extends Controller
{
    public function create(): View|RedirectResponse
    {
        $business = DB::table('businesses')->where('owner_id', auth()->id())->latest()->first();
        if (! $business) {
            return redirect()->route('business.create')->with('status', 'Create your business profile before adding transaction evidence.');
        }

        return view('transactions.create', compact('business'));
    }

    public function store(Request $request): RedirectResponse
    {
        $business = DB::table('businesses')->where('owner_id', auth()->id())->latest()->first();
        if (! $business) {
            return redirect()->route('business.create')->withInput()->with('status', 'Create your business profile before adding transaction evidence.');
        }

        $validated = $request->validate([
            'phone_number' => ['required', 'string', 'max:30'],
            'provider' => ['required', 'string', 'max:100'],
            'reference' => ['required', 'string', 'max:100', 'unique:business_transactions,reference'],
            'amount' => ['required', 'numeric', 'min:1'],
            'occurred_at' => ['required', 'date'],
        ]);

        DB::table('business_transactions')->insert([
            ...$validated,
            'business_id' => $business->id,
            'transaction_type' => 'credit',
            'verification_status' => 'pending',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return redirect()->route('dashboard')->with('status', 'Transaction evidence added for verification.');
    }
}
