<?php

namespace App\Http\Controllers;

use App\Services\Pricing\LoyaltyService;
use Illuminate\Http\Request;

class RewardsController extends Controller
{
    public function index(Request $request, LoyaltyService $loyalty)
    {
        $user = $request->user();
        $balance = $user->pointsBalance();

        return view('rewards.index', [
            'balance' => $balance,
            'value' => $loyalty->valueOf($balance),
            'transactions' => $user->loyaltyTransactions()->with('booking')->latest()->paginate(20),
            'loyalty' => $loyalty,
        ]);
    }
}
