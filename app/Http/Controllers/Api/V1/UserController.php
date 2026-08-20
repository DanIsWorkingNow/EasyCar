<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Services\UserRoleSyncService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

/**
 * TSD Section 8.7. Uses UserRoleSyncService so role-sync and audit logging
 * (FR-USR-05, TD-22) happen identically here and in the web
 * Admin\UserController, rather than being implemented twice.
 */
class UserController extends Controller
{
    public function index(Request $request)
    {
        abort_unless($request->user()->can('manage-users'), 403);

        return UserResource::collection(User::with(['branch', 'roles'])->paginate(15));
    }

    public function store(Request $request, UserRoleSyncService $roleSync)
    {
        abort_unless($request->user()->can('manage-users'), 403);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
            'userLevel' => 'required|integer|in:1,5',
            'branch_id' => 'nullable|exists:branches,id',
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'userLevel' => $validated['userLevel'],
            'branch_id' => $validated['branch_id'] ?? null,
        ]);

        $roleSync->syncAndLog($user, (int) $validated['userLevel'], $request->user()->id);

        return (new UserResource($user->load('branch', 'roles')))->response()->setStatusCode(201);
    }

    public function update(Request $request, User $user, UserRoleSyncService $roleSync)
    {
        abort_unless($request->user()->can('manage-users'), 403);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email,' . $user->id,
            'userLevel' => 'required|integer|in:1,5',
            'branch_id' => 'nullable|exists:branches,id',
        ]);

        $updateData = [
            'name' => $validated['name'],
            'email' => $validated['email'],
            'userLevel' => $validated['userLevel'],
            'branch_id' => $validated['branch_id'] ?? null,
        ];

        if ($request->filled('password')) {
            $request->validate(['password' => 'string|min:8|confirmed']);
            $updateData['password'] = Hash::make($request->password);
        }

        $user->update($updateData);
        $roleSync->syncAndLog($user, (int) $validated['userLevel'], $request->user()->id);

        return new UserResource($user->fresh(['branch', 'roles']));
    }

    public function destroy(Request $request, User $user)
    {
        abort_unless($request->user()->can('manage-users'), 403);

        $user->delete();

        return response()->json(['data' => ['message' => 'User deactivated.']]);
    }
}
