<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\User;
use App\Services\UserRoleSyncService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

/**
 * FIXED — TD-22. After Level 1 Part 2's cutover, authorization everywhere
 * else reads Spatie roles (hasRole()/can()) — but this controller's
 * store()/update() only ever wrote to the old `userLevel` column.
 * RolesAndPermissionsSeeder backfills roles once at seed time, but nothing
 * kept them in sync afterward: creating a new Staff/Admin user through this
 * form, or changing an existing user's userLevel from Staff to Admin,
 * updated the (by then vestigial) userLevel column only. The user's actual
 * Spatie role — the thing every policy and route middleware actually
 * checks — never changed. This version syncs the Spatie role every time
 * userLevel is set, and logs the change to role_audit_logs (FR-USR-05).
 *
 * Kept `userLevel` in the form/validation as-is — it's just no longer the
 * only thing being written.
 *
 * Role-sync/audit logic lives in UserRoleSyncService (API kit) so it isn't
 * implemented twice between this controller and the API's UserController.
 */
class UserController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $users = User::with(['branch', 'roles'])
            ->when($request->filled('search'), function ($q) use ($request) {
                $term = strtolower(trim($request->search));
                $q->where(fn ($qq) => $qq->whereRaw('LOWER(name) LIKE ?', ["%{$term}%"])
                    ->orWhereRaw('LOWER(email) LIKE ?', ["%{$term}%"]));
            })
            ->when($request->filled('role'), fn ($q) => $q->role($request->role))
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        return view('admin.users.index', compact('users'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $branches = Branch::orderBy('name')->get();

        return view('admin.users.create', compact('branches'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request, UserRoleSyncService $roleSync)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
            'userLevel' => 'required|integer|in:0,1,5', // 0 customer, 1 staff, 5 admin
            'branch_id' => 'nullable|exists:branches,id',
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'userLevel' => $request->userLevel,
            'branch_id' => $request->branch_id,
        ]);

        $roleSync->syncAndLog($user, (int) $request->userLevel, Auth::id());

        return redirect()->route('admin.users.index')->with('success', 'User created successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show(User $user)
    {
        return redirect()->route('admin.users.edit', $user);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(User $user)
    {
        $branches = Branch::orderBy('name')->get();

        return view('admin.users.edit', compact('user', 'branches'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, User $user, UserRoleSyncService $roleSync)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email,'.$user->id,
            'userLevel' => 'required|integer|in:0,1,5',
            'branch_id' => 'nullable|exists:branches,id',
        ]);

        $updateData = [
            'name' => $request->name,
            'email' => $request->email,
            'userLevel' => $request->userLevel,
            'branch_id' => $request->branch_id,
        ];

        // Only update password if provided
        if ($request->filled('password')) {
            $request->validate([
                'password' => 'string|min:8|confirmed',
            ]);
            $updateData['password'] = Hash::make($request->password);
        }

        $user->update($updateData);

        $roleSync->syncAndLog($user, (int) $request->userLevel, Auth::id());

        return redirect()->route('admin.users.index')->with('success', 'User updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(User $user)
    {
        if ($user->is(Auth::user())) {
            return back()->with('error', 'You cannot delete your own account.');
        }

        $user->delete();

        return redirect()->route('admin.users.index')->with('success', 'User deleted successfully.');
    }
}
