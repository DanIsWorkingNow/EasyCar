<?php

namespace App\Services;

use App\Models\RoleAuditLog;
use App\Models\User;

/**
 * Extracted from Level 3's Admin\UserController::syncRoleAndLog() so the
 * API's UserController doesn't duplicate the same logic a second time.
 * Behavior is identical to Level 3's version — see TD-22: userLevel and the
 * Spatie role can drift apart if only one of them is ever written.
 */
class UserRoleSyncService
{
    private const LEVEL_TO_ROLE = [1 => 'staff', 5 => 'admin'];

    public function roleNameForLevel(int $userLevel): string
    {
        return self::LEVEL_TO_ROLE[$userLevel] ?? 'customer';
    }

    public function syncAndLog(User $user, int $newUserLevel, ?int $changedByUserId): void
    {
        $oldRole = $user->roles->first()?->name;
        $newRole = $this->roleNameForLevel($newUserLevel);

        if ($oldRole === $newRole) {
            return;
        }

        $user->syncRoles([$newRole]);

        RoleAuditLog::create([
            'user_id' => $user->id,
            'changed_by' => $changedByUserId,
            'old_role' => $oldRole,
            'new_role' => $newRole,
        ]);
    }
}
