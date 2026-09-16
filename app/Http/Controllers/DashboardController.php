<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(): View|RedirectResponse
    {
        $user = auth()->user();
        $role = $user->role ?? 'business';

        if ($role === 'admin') {
            return redirect()->route('admin.dashboard');
        }
        $business = DB::table('businesses')->where('owner_id', $user->id)->latest()->first();
        $transactions = $business
            ? DB::table('business_transactions')->where('business_id', $business->id)->latest('occurred_at')->limit(5)->get()
            : collect();
        $transactionTotal = $business
            ? (float) DB::table('business_transactions')->where('business_id', $business->id)->where('transaction_type', 'credit')->where('verification_status', 'verified')->sum('amount')
            : 0;
        $capital = (float) ($business->capital ?? 0);
        $eligibilityRatio = $capital > 0 ? round(($transactionTotal / $capital) * 100, 1) : 0;
        $application = $business
            ? DB::table('funding_applications')->where('business_id', $business->id)->latest()->first()
            : null;

        return view('dashboard', [
            'business' => $business,
            'transactions' => $transactions,
            'application' => $application,
            'transactionTotal' => $transactionTotal,
            'eligibilityRatio' => $eligibilityRatio,
            'eligible' => $eligibilityRatio > 30,
            'activeApplications' => DB::table('funding_applications')->whereIn('status', ['submitted', 'under_review', 'approved'])->count(),
            'approvedApplications' => DB::table('funding_applications')->where('status', 'approved')->count(),
            'providerCount' => DB::table('fund_providers')->where('status', 'approved')->count(),
            'hospitalCount' => DB::table('hospitals')->where('status', 'approved')->count(),
            'role' => $role,
        ]);
    }
}
