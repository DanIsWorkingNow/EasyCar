@extends('layouts.console')

@section('title', 'Users')
@section('breadcrumb', 'Admin console / Users')

@section('content')
    <x-page-header eyebrow="Access" title="Users" subtitle="Staff are scoped to their branch; admins see everything. Role changes are audited.">
        <x-slot:actions>
            <a href="{{ route('admin.users.create') }}" class="btn btn-primary"><x-icon name="plus" class="size-4" /> Add staff or admin</a>
        </x-slot:actions>
    </x-page-header>

    <form method="GET" class="card mb-6 grid gap-3 p-4 sm:grid-cols-[1.6fr_1fr_auto] sm:items-end">
        <label class="block"><span class="label">Search</span><input name="search" value="{{ request('search') }}" placeholder="Name or email" class="input"></label>
        <x-field name="role" label="Role" type="select" placeholder="All roles" :value="request('role')" :options="['admin' => 'Admin', 'staff' => 'Staff', 'customer' => 'Customer']" />
        <div class="flex gap-2"><button class="btn btn-primary">Filter</button>@if (request()->hasAny(['search', 'role']))<a href="{{ route('admin.users.index') }}" class="btn btn-outline">Clear</a>@endif</div>
    </form>

    <div class="card overflow-x-auto">
        <table class="table-ec">
            <thead><tr><th>User</th><th>Role</th><th>Branch</th><th>Joined</th><th class="text-right">Actions</th></tr></thead>
            <tbody>
                @forelse ($users as $user)
                    @php($role = $user->roles->first()?->name ?? 'customer')
                    <tr>
                        <td><div class="flex items-center gap-3"><x-avatar :user="$user" /><div><p class="font-medium">{{ $user->name }} @if ($user->is(auth()->user()))<span class="text-xs text-subtle">(you)</span>@endif</p><p class="text-xs text-subtle">{{ $user->email }}</p></div></div></td>
                        <td><x-badge :tone="['admin' => 'brand', 'staff' => 'info'][$role] ?? 'neutral'">{{ ucfirst($role) }}</x-badge></td>
                        <td class="text-mute">{{ $user->branch?->name ?? '—' }}</td>
                        <td class="text-mute">{{ $user->created_at?->format('d M Y') }}</td>
                        <td class="text-right whitespace-nowrap">
                            <a href="{{ route('admin.users.edit', $user) }}" class="btn btn-outline btn-sm"><x-icon name="pencil" class="size-4" /> Edit</a>
                            @unless ($user->is(auth()->user()))
                                <form method="POST" action="{{ route('admin.users.destroy', $user) }}" class="inline" data-confirm="Delete {{ $user->name }}? Their bookings will be removed too.">
                                    @csrf @method('DELETE')
                                    <button class="btn btn-quiet btn-sm" aria-label="Delete {{ $user->name }}"><x-icon name="trash" class="size-4" /></button>
                                </form>
                            @endunless
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="py-10 text-center text-subtle">No users found.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-6">{{ $users->links() }}</div>
@endsection
