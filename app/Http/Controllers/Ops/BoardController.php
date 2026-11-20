<?php

namespace App\Http\Controllers\Ops;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Services\Rentals\RentalOperationsService;
use Illuminate\Http\Request;

class BoardController extends Controller
{
    public function index(Request $request, RentalOperationsService $ops)
    {
        $user = $request->user();
        $branchId = $user->isAdmin() ? ($request->integer('branch_id') ?: null) : $user->branch_id;

        abort_if(! $user->isAdmin() && ! $branchId, 403, 'Your account is not assigned to a branch.');

        return view('ops.board', [
            'buckets' => $ops->board($branchId),
            'branches' => $user->isAdmin() ? Branch::orderBy('name')->get() : collect(),
            'branchId' => $branchId,
        ]);
    }
}
