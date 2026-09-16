<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class AdminDashboardController extends Controller
{
    public function __invoke(): View
    {
        return view('admin.dashboard', [
            'userCount' => DB::table('users')->count(),
            'businessCount' => DB::table('businesses')->count(),
            'providerCount' => DB::table('fund_providers')->count(),
            'hospitalCount' => DB::table('hospitals')->count(),
            'applicationCount' => DB::table('funding_applications')->count(),
            'pendingApplications' => DB::table('funding_applications')->whereIn('status', ['draft', 'submitted', 'under_review'])->count(),
            'pendingProviders' => DB::table('fund_providers')->where('status', 'pending')->count(),
            'pendingHospitals' => DB::table('hospitals')->where('status', 'pending')->count(),
            'transactions' => DB::table('business_transactions')->join('businesses', 'business_transactions.business_id', '=', 'businesses.id')->select('business_transactions.*', 'businesses.name as business_name')->latest('business_transactions.created_at')->limit(8)->get(),
            'applications' => DB::table('funding_applications')->join('businesses', 'funding_applications.business_id', '=', 'businesses.id')->select('funding_applications.*', 'businesses.name as business_name')->latest('funding_applications.created_at')->limit(8)->get(),
        ]);
    }

    public function updateApplication(Request $request, int $application): RedirectResponse
    {
        $status = $request->validate(['status' => ['required', 'in:draft,submitted,under_review,approved,rejected,funded']])['status'];
        DB::table('funding_applications')->where('id', $application)->update(['status' => $status, 'updated_at' => now()]);
        DB::table('audit_events')->insert(['actor_id' => auth()->id(), 'event' => 'application.status_updated', 'auditable_type' => 'funding_application', 'auditable_id' => $application, 'metadata' => json_encode(['status' => $status]), 'created_at' => now(), 'updated_at' => now()]);

        return back()->with('status', 'Application status updated.');
    }

    public function updateProvider(Request $request, int $provider): RedirectResponse
    {
        $status = $request->validate(['status' => ['required', 'in:pending,approved,suspended,rejected']])['status'];
        DB::table('fund_providers')->where('id', $provider)->update(['status' => $status, 'updated_at' => now()]);

        return back()->with('status', 'Provider status updated.');
    }

    public function updateHospital(Request $request, int $hospital): RedirectResponse
    {
        $status = $request->validate(['status' => ['required', 'in:pending,approved,suspended,rejected']])['status'];
        DB::table('hospitals')->where('id', $hospital)->update(['status' => $status, 'updated_at' => now()]);

        return back()->with('status', 'Hospital status updated.');
    }
}
