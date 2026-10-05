<?php

namespace App\Http\Controllers;

use App\Services\TransactionVerificationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class TransactionSyncController extends Controller
{
    public function __invoke(Request $request, TransactionVerificationService $verification): RedirectResponse
    {
        $result = $verification->syncForUser($request->user());

        if ($result['status'] !== 'synced') {
            return back()->with('error', $result['message']);
        }

        return back()->with('status', $result['message']);
    }
}
