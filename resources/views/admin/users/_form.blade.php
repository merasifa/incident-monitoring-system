@php($isEdit = ($mode ?? 'create') === 'edit')

<div class="grid gap-5 md:grid-cols-2">
    <div>
        <x-input-label for="name" value="Name" />
        <x-text-input id="name" name="name" type="text" class="mt-1 block w-full" value="{{ old('name', $user->name) }}" required autofocus />
        <x-input-error class="mt-2" :messages="$errors->get('name')" />
    </div>

    <div>
        <x-input-label for="email" value="Email" />
        <x-text-input id="email" name="email" type="email" class="mt-1 block w-full" value="{{ old('email', $user->email) }}" required />
        <x-input-error class="mt-2" :messages="$errors->get('email')" />
    </div>

    <div>
        <x-input-label for="password" value="Password {{ $isEdit ? '(leave blank to keep current)' : '' }}" />
        <x-text-input id="password" name="password" type="password" class="mt-1 block w-full" {{ $isEdit ? '' : 'required' }} />
        <x-input-error class="mt-2" :messages="$errors->get('password')" />
    </div>

    <div>
        <x-input-label for="password_confirmation" value="Confirm Password" />
        <x-text-input id="password_confirmation" name="password_confirmation" type="password" class="mt-1 block w-full" />
    </div>

    <div>
        <x-input-label for="role" value="Role" />
        <select id="role" name="role" class="mt-1 block w-full rounded-xl border-slate-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" required>
            <option value="operator" @selected(old('role', $user->role) === 'operator')>Operator</option>
            <option value="admin" @selected(old('role', $user->role) === 'admin')>Admin</option>
        </select>
        <x-input-error class="mt-2" :messages="$errors->get('role')" />
    </div>
</div>

<div class="mt-6 flex items-center gap-3">
    <x-primary-button>{{ $isEdit ? 'Update User' : 'Create User' }}</x-primary-button>
    <a href="{{ route('admin.users.index') }}" class="rounded-xl border border-slate-200 px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">Cancel</a>
</div>