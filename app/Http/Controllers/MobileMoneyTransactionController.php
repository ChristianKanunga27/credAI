<?php

namespace App\Http\Controllers;

use App\Models\MobileMoneyTransaction;
use App\Services\TransactionVerificationService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MobileMoneyTransactionController extends Controller
{
    public function index(Request $request, TransactionVerificationService $transactions): View
    {
        return view('transactions.mobile-money', [
            'availableBalance' => $transactions->availableBalanceForUser($request->user())['balance'],
            'transactions' => MobileMoneyTransaction::query()
                ->whereBelongsTo($request->user())
                ->where('verification_status', 'verified')
                ->latest('occurred_at')
                ->paginate(25),
        ]);
    }
}
