@php($editing = isset($user) && $user->exists)
<form method="POST" action="{{ $editing ? route('admin.users.update', $user) : route('admin.users.store') }}" class="card max-w-3xl" x-data="{ level: '{{ old('userLevel', $editing ? ($user->hasRole('admin') ? 5 : ($user->hasRole('staff') ? 1 : 0)) : 1) }}' }">
    @csrf
    @if ($editing) @method('PUT') @endif
    <div class="card-pad space-y-5">
        <div class="grid gap-4 sm:grid-cols-2">
            <x-field name="name" label="Full name" :value="$user->name ?? ''" required />
            <x-field name="email" label="Email" type="email" :value="$user->email ?? ''" required />
        </div>
        <div class="grid gap-4 sm:grid-cols-2">
            <div>
                <label class="label" for="userLevel">Role <span class="text-brand">*</span></label>
                <select id="userLevel" name="userLevel" x-model="level" class="input @error('userLevel') input-error @enderror" required>
                    <option value="0">Customer — books and rents cars</option>
                    <option value="1">Staff — approve bookings for one branch</option>
                    <option value="5">Admin — full access</option>
                </select>
                @error('userLevel')<p class="field-error">{{ $message }}</p>@enderror
            </div>
            <x-field name="branch_id" label="Branch" type="select" :value="$user->branch_id ?? ''" :options="$branches->pluck('name', 'id')->all()" placeholder="No branch" help="Required for staff — it scopes what they can see." />
        </div>
        <div class="grid gap-4 sm:grid-cols-2">
            <x-field name="password" label="{{ $editing ? 'New password' : 'Password' }}" type="password" :required="! $editing" autocomplete="new-password" :help="$editing ? 'Leave blank to keep the current password.' : 'At least 8 characters.'" />
            <x-field name="password_confirmation" label="Confirm password" type="password" autocomplete="new-password" />
        </div>
    </div>
    <div class="flex justify-end gap-2 border-t border-line px-6 py-4">
        <a href="{{ route('admin.users.index') }}" class="btn btn-quiet">Cancel</a>
        <button class="btn btn-primary">{{ $editing ? 'Save changes' : 'Create user' }}</button>
    </div>
</form>
