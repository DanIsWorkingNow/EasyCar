@extends('layouts.app')

@section('content')
<div class="container" style="max-width: 640px;">

    <div style="background: rgba(255,255,255,0.95); backdrop-filter: blur(15px); border-radius: 25px; padding: 2.5rem; box-shadow: 0 20px 40px rgba(0,0,0,0.1); position: relative; overflow: hidden;">
        <div style="position: absolute; top: -30px; right: -30px; width: 80px; height: 80px; background: linear-gradient(45deg, #667eea, #764ba2); border-radius: 50%; opacity: 0.1;"></div>

        <div style="display: flex; align-items: center; gap: 0.85rem; margin-bottom: 2rem;">
            <div style="width: 52px; height: 52px; border-radius: 50%; background: linear-gradient(45deg, #667eea, #764ba2); color: #fff; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 1.1rem; flex-shrink: 0;">
                {{ collect(explode(' ', $user->name))->map(fn($p) => strtoupper(substr($p, 0, 1)))->take(2)->implode('') ?: '?' }}
            </div>
            <div>
                <h2 style="font-size: 1.75rem; font-weight: 800; background: linear-gradient(45deg, #667eea, #764ba2); -webkit-background-clip: text; -webkit-text-fill-color: transparent; margin: 0;">
                    Edit User
                </h2>
                <p style="color: #6b7280; margin: 0.15rem 0 0 0; font-size: 0.9rem;">{{ $user->name }} · {{ $user->email }}</p>
            </div>
        </div>

        @if ($errors->any())
            <div style="background: #fef2f2; border: 1px solid #fecaca; color: #b91c1c; padding: 1rem 1.25rem; border-radius: 14px; margin-bottom: 1.5rem;">
                <ul style="margin: 0; padding-left: 1.1rem;">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form action="{{ route('admin.users.update', $user) }}" method="POST">
            @csrf
            @method('PUT')

            <div style="margin-bottom: 1.25rem;">
                <label style="display: block; font-size: 0.85rem; font-weight: 700; color: #374151; margin-bottom: 0.4rem;">Name</label>
                <input type="text" name="name" value="{{ old('name', $user->name) }}" required
                       style="width: 100%; padding: 0.75rem 1rem; border-radius: 12px; border: 1px solid #e5e7eb; outline: none; font-size: 0.95rem;">
            </div>

            <div style="margin-bottom: 1.25rem;">
                <label style="display: block; font-size: 0.85rem; font-weight: 700; color: #374151; margin-bottom: 0.4rem;">Email</label>
                <input type="email" name="email" value="{{ old('email', $user->email) }}" required
                       style="width: 100%; padding: 0.75rem 1rem; border-radius: 12px; border: 1px solid #e5e7eb; outline: none; font-size: 0.95rem;">
            </div>

            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem; margin-bottom: 0.4rem;">
                <div>
                    <label style="display: block; font-size: 0.85rem; font-weight: 700; color: #374151; margin-bottom: 0.4rem;">New Password</label>
                    <input type="password" name="password" placeholder="Leave blank to keep current"
                           style="width: 100%; padding: 0.75rem 1rem; border-radius: 12px; border: 1px solid #e5e7eb; outline: none; font-size: 0.95rem;">
                </div>
                <div>
                    <label style="display: block; font-size: 0.85rem; font-weight: 700; color: #374151; margin-bottom: 0.4rem;">Confirm New Password</label>
                    <input type="password" name="password_confirmation"
                           style="width: 100%; padding: 0.75rem 1rem; border-radius: 12px; border: 1px solid #e5e7eb; outline: none; font-size: 0.95rem;">
                </div>
            </div>
            <p style="color: #9ca3af; font-size: 0.8rem; margin: 0 0 1.25rem 0;">Only fill these in if you want to reset the password.</p>

            <div style="margin-bottom: 1.25rem;">
                <label style="display: block; font-size: 0.85rem; font-weight: 700; color: #374151; margin-bottom: 0.4rem;">Role</label>
                <select name="userLevel" required
                        style="width: 100%; padding: 0.75rem 1rem; border-radius: 12px; border: 1px solid #e5e7eb; outline: none; font-size: 0.95rem; background: #fff;">
                    <option value="1" {{ old('userLevel', $user->userLevel) == 1 ? 'selected' : '' }}>Staff</option>
                    <option value="5" {{ old('userLevel', $user->userLevel) == 5 ? 'selected' : '' }}>Admin</option>
                </select>
            </div>

            <div style="margin-bottom: 2rem;">
                <label style="display: block; font-size: 0.85rem; font-weight: 700; color: #374151; margin-bottom: 0.4rem;">Branch</label>
                <select name="branch_id"
                        style="width: 100%; padding: 0.75rem 1rem; border-radius: 12px; border: 1px solid #e5e7eb; outline: none; font-size: 0.95rem; background: #fff;">
                    <option value="">— None —</option>
                    @foreach($branches as $branch)
                        <option value="{{ $branch->id }}" {{ old('branch_id', $user->branch_id) == $branch->id ? 'selected' : '' }}>{{ $branch->name }}</option>
                    @endforeach
                </select>
            </div>

            <div style="display: flex; gap: 0.75rem;">
                <button type="submit"
                        style="background: linear-gradient(45deg, #667eea, #764ba2); color: #fff; border: none; padding: 0.8rem 1.75rem; border-radius: 25px; font-weight: 700; cursor: pointer; box-shadow: 0 10px 25px rgba(102, 126, 234, 0.3); transition: all .2s ease;"
                        onmouseover="this.style.transform='translateY(-2px)'" onmouseout="this.style.transform='translateY(0)'">
                    Save Changes
                </button>
                <a href="{{ route('admin.users.index') }}"
                   style="padding: 0.8rem 1.75rem; border-radius: 25px; font-weight: 700; text-decoration: none; color: #4b5563; background: #f3f4f6; transition: all .2s ease;"
                   onmouseover="this.style.background='#e5e7eb'" onmouseout="this.style.background='#f3f4f6'">
                    Cancel
                </a>
            </div>
        </form>
    </div>
</div>
@endsection
