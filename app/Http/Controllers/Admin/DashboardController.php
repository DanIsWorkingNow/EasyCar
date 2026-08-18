<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;

/**
 * REPLACED for Level 2. All the actual data logic has moved into
 * DashboardService + the Livewire components under app/Livewire/Dashboard —
 * this controller's only job now is to render the page that hosts them.
 */
class DashboardController extends Controller
{
    public function index()
    {
        return view('admin.dashboard');
    }
}
