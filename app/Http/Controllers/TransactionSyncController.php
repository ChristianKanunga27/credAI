<?php

namespace App\Http\Controllers;

use App\Services\TransactionVerificationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;

class TransactionSyncController extends Controller
{
    public function __invoke(TransactionVerificationService $verification): RedirectResponse
    {
        $business = DB::table('businesses')->where('owner_id', auth()->id())->latest()->first();
        abort_unless($business && auth()->user()->phone, 422, 'Add a phone number and business profile before syncing transactions.');

        $result = $verification->syncForBusiness($business->id, auth()->user()->phone);

        return back()->with($result['status'] === 'synced' ? 'status' : 'error', $result['message']);
    }
}
