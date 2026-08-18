<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;

/**
 * REPLACED for Level 2 — see the comment in Admin\DashboardController.
 * The branch-scoping that used to live here (Auth::user()->branch_id) now
 * happens inside Livewire\Dashboard\DashboardIndex::mount().
 */
class DashboardController extends Controller
{
    public function index()
    {
        return view('staff.dashboard');
    }
}
