<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class FundingApplicationController extends Controller
{
    public function create(): View|RedirectResponse
    {
        $business = DB::table('businesses')->where('owner_id', auth()->id())->latest()->first();
        if (! $business) {
            return redirect()->route('business.create')->with('status', 'Create your business profile before applying for funding.');
        }

        $providers = DB::table('fund_providers')->where('status', 'approved')->orderBy('organization_name')->get();
        $hospitals = DB::table('hospitals')->where('status', 'approved')->orderBy('name')->get();

        return view('funding.create', compact('business', 'providers', 'hospitals'));
    }

    public function store(Request $request): RedirectResponse
    {
        $business = DB::table('businesses')->where('owner_id', auth()->id())->latest()->first();
        if (! $business) {
            return redirect()->route('business.create')->withInput()->with('status', 'Create your business profile before applying for funding.');
        }

        $validated = $request->validate([
            'requested_amount' => ['required', 'numeric', 'min:1'],
            'purpose' => ['required', 'string', 'max:500'],
            'provider_id' => ['nullable', 'exists:fund_providers,id'],
            'hospital_id' => ['nullable', 'exists:hospitals,id'],
        ]);

        $credits = (float) DB::table('business_transactions')->where('business_id', $business->id)->where('transaction_type', 'credit')->sum('amount');
        $ratio = $business->capital > 0 ? round(($credits / $business->capital) * 100, 2) : 0;

        DB::table('funding_applications')->insert([
            'business_id' => $business->id,
            'provider_id' => $validated['provider_id'] ?? null,
            'hospital_id' => $validated['hospital_id'] ?? null,
            'requested_amount' => $validated['requested_amount'],
            'purpose' => $validated['purpose'],
            'status' => $ratio > 30 ? 'submitted' : 'draft',
            'eligibility_ratio' => $ratio,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return redirect()->route('dashboard')->with('status', 'Funding application submitted successfully.');
    }
}
