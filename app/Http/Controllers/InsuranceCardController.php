<?php

namespace App\Http\Controllers;

use App\Models\InsurancePolicy;
use App\Models\InsuranceProvider;
use Illuminate\Http\Request;
use Illuminate\View\View;

class InsuranceCardController extends Controller
{
    public function __invoke(Request $request): View
    {
        $policy = InsurancePolicy::query()
            ->whereBelongsTo($request->user())
            ->where('status', 'active')
            ->whereDate('start_date', '<=', today())
            ->whereDate('end_date', '>=', today())
            ->with('profile')
            ->latest('start_date')
            ->first();

        $hospitals = InsuranceProvider::query()
            ->where('status', 'approved')
            ->where('provider_type', 'hospital')
            ->with(['services' => fn ($services) => $services->where('is_active', true)->orderBy('name')])
            ->orderBy('organization_name')
            ->get(['id', 'organization_name', 'license_number']);

        return view('insurance.card', compact('policy', 'hospitals'));
    }
}
