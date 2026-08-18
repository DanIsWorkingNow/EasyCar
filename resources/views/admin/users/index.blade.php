@extends('layouts.app')

@section('content')
<div class="container" style="max-width: 1100px;">

    <!-- Header -->
    <div style="background: rgba(255,255,255,0.95); backdrop-filter: blur(15px); border-radius: 25px; padding: 2rem 2.5rem; margin-bottom: 1.5rem; box-shadow: 0 20px 40px rgba(0,0,0,0.1); position: relative; overflow: hidden;">
        <div style="position: absolute; top: -30px; right: -30px; width: 80px; height: 80px; background: linear-gradient(45deg, #f59e0b, #d97706); border-radius: 50%; opacity: 0.1;"></div>

        <div style="display: flex; align-items: center; justify-content: space-between; gap: 1rem; flex-wrap: wrap;">
            <div style="display: flex; align-items: center; gap: 1rem;">
                <div style="font-size: 2.75rem;">👥</div>
                <div>
                    <h1 style="font-size: 2rem; font-weight: 800; background: linear-gradient(45deg, #f59e0b, #d97706); -webkit-background-clip: text; -webkit-text-fill-color: transparent; margin: 0; line-height: 1.2;">
                        User Management
                    </h1>
                    <p style="color: #6b7280; margin: 0.25rem 0 0 0;">
                        {{ $users->total() }} {{ Str::plural('account', $users->total()) }} registered
                    </p>
                </div>
            </div>

            <a href="{{ route('admin.users.create') }}"
               style="display: inline-flex; align-items: center; gap: 0.5rem; background: linear-gradient(45deg, #667eea, #764ba2); color: white; padding: 0.75rem 1.5rem; border-radius: 25px; text-decoration: none; font-weight: 600; box-shadow: 0 10px 25px rgba(102, 126, 234, 0.3); transition: all 0.2s ease;"
               onmouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='0 15px 30px rgba(102, 126, 234, 0.4)'"
               onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 10px 25px rgba(102, 126, 234, 0.3)'">
                <span style="font-size: 1.1rem;">＋</span> Add New User
            </a>
        </div>
    </div>

    @if(session('success'))
        <div style="background: #d1fae5; border: 1px solid #6ee7b7; color: #065f46; padding: 0.9rem 1.25rem; border-radius: 14px; margin-bottom: 1.5rem; font-weight: 600;">
            ✅ {{ session('success') }}
        </div>
    @endif

    <!-- Search -->
    <div style="margin-bottom: 1rem;">
        <input type="text" id="userSearch" onkeyup="filterUsers()" placeholder="🔍 Search by name, email, or role..."
               style="width: 100%; padding: 0.85rem 1.25rem; border-radius: 14px; border: 1px solid #e5e7eb; box-shadow: 0 4px 12px rgba(0,0,0,0.05); font-size: 0.95rem; outline: none;">
    </div>

    <!-- Table Card -->
    <div style="background: rgba(255,255,255,0.95); backdrop-filter: blur(15px); border-radius: 20px; box-shadow: 0 20px 40px rgba(0,0,0,0.08); overflow: hidden;">
        <div style="overflow-x: auto;">
            <table style="width: 100%; border-collapse: collapse;">
                <thead>
                    <tr style="background: linear-gradient(135deg, #f8fafc 0%, #eef2ff 100%);">
                        <th style="text-align: left; padding: 1rem 1.5rem; font-size: 0.8rem; letter-spacing: 0.05em; text-transform: uppercase; color: #6b7280; font-weight: 700;">User</th>
                        <th style="text-align: left; padding: 1rem 1.5rem; font-size: 0.8rem; letter-spacing: 0.05em; text-transform: uppercase; color: #6b7280; font-weight: 700;">Email</th>
                        <th style="text-align: left; padding: 1rem 1.5rem; font-size: 0.8rem; letter-spacing: 0.05em; text-transform: uppercase; color: #6b7280; font-weight: 700;">Role</th>
                        <th style="text-align: left; padding: 1rem 1.5rem; font-size: 0.8rem; letter-spacing: 0.05em; text-transform: uppercase; color: #6b7280; font-weight: 700;">Branch</th>
                        <th style="text-align: right; padding: 1rem 1.5rem; font-size: 0.8rem; letter-spacing: 0.05em; text-transform: uppercase; color: #6b7280; font-weight: 700;">Actions</th>
                    </tr>
                </thead>
                <tbody id="userTableBody">
                    @forelse($users as $user)
                        @php
                            $roleLabel = $user->userLevel == 5 ? 'Admin' : ($user->userLevel == 1 ? 'Staff' : 'Customer');
                            $roleColors = [
                                'Admin'    => ['bg' => '#fee2e2', 'text' => '#b91c1c', 'grad' => 'linear-gradient(45deg, #ef4444, #dc2626)'],
                                'Staff'    => ['bg' => '#dbeafe', 'text' => '#1d4ed8', 'grad' => 'linear-gradient(45deg, #3b82f6, #2563eb)'],
                                'Customer' => ['bg' => '#d1fae5', 'text' => '#047857', 'grad' => 'linear-gradient(45deg, #10b981, #059669)'],
                            ];
                            $rc = $roleColors[$roleLabel];
                            $initials = collect(explode(' ', $user->name))->map(fn($p) => strtoupper(substr($p, 0, 1)))->take(2)->implode('');
                        @endphp
                        <tr class="user-row" style="border-top: 1px solid #f1f5f9; transition: background .15s ease;"
                            onmouseover="this.style.background='#f8fafc'" onmouseout="this.style.background='transparent'">
                            <td style="padding: 1rem 1.5rem;">
                                <div style="display: flex; align-items: center; gap: 0.75rem;">
                                    <div style="width: 40px; height: 40px; border-radius: 50%; background: {{ $rc['grad'] }}; color: #fff; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 0.85rem; flex-shrink: 0;">
                                        {{ $initials ?: '?' }}
                                    </div>
                                    <span style="font-weight: 600; color: #1f2937;" class="cell-name">{{ $user->name }}</span>
                                </div>
                            </td>
                            <td style="padding: 1rem 1.5rem; color: #4b5563;" class="cell-email">{{ $user->email }}</td>
                            <td style="padding: 1rem 1.5rem;">
                                <span class="cell-role" style="display: inline-block; padding: 0.3rem 0.85rem; border-radius: 20px; font-size: 0.8rem; font-weight: 700; background: {{ $rc['bg'] }}; color: {{ $rc['text'] }};">
                                    {{ $roleLabel }}
                                </span>
                            </td>
                            <td style="padding: 1rem 1.5rem; color: #4b5563;">{{ $user->branch->name ?? '—' }}</td>
                            <td style="padding: 1rem 1.5rem; text-align: right;">
                                <div style="display: inline-flex; gap: 0.5rem;">
                                    <a href="{{ route('admin.users.edit', $user) }}" title="Edit"
                                       style="display: inline-flex; align-items: center; justify-content: center; width: 34px; height: 34px; border-radius: 10px; background: #eef2ff; color: #4f46e5; text-decoration: none; transition: transform .15s ease;"
                                       onmouseover="this.style.transform='scale(1.08)'" onmouseout="this.style.transform='scale(1)'">
                                        ✏️
                                    </a>
                                    <form action="{{ route('admin.users.destroy', $user) }}" method="POST" class="d-inline" onsubmit="return confirm('Delete this user? This cannot be undone.');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" title="Delete"
                                                style="display: inline-flex; align-items: center; justify-content: center; width: 34px; height: 34px; border-radius: 10px; background: #fef2f2; color: #dc2626; border: none; cursor: pointer; transition: transform .15s ease;"
                                                onmouseover="this.style.transform='scale(1.08)'" onmouseout="this.style.transform='scale(1)'">
                                            🗑️
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" style="padding: 3rem; text-align: center; color: #9ca3af;">
                                No users found.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div style="margin-top: 1.5rem;">
        {{ $users->links() }}
    </div>
</div>

<script>
    function filterUsers() {
        const query = document.getElementById('userSearch').value.trim().toLowerCase();
        document.querySelectorAll('#userTableBody .user-row').forEach(row => {
            const name = row.querySelector('.cell-name')?.textContent.toLowerCase() ?? '';
            const email = row.querySelector('.cell-email')?.textContent.toLowerCase() ?? '';
            const role = row.querySelector('.cell-role')?.textContent.toLowerCase() ?? '';
            row.style.display = (name.includes(query) || email.includes(query) || role.includes(query)) ? '' : 'none';
        });
    }
</script>
@endsection
