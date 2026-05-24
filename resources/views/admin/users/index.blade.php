<x-app-layout>
    <div class="space-y-5">
        <div class="flex flex-col gap-3 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm md:flex-row md:items-center md:justify-between">
            <div>
                <h1 class="text-3xl font-bold text-slate-800">User Management</h1>
                <p class="mt-1 text-sm text-slate-500">Manage admin and operator accounts.</p>
            </div>
            <a href="{{ route('admin.users.create') }}" class="inline-flex items-center justify-center rounded-xl bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-700">Add User</a>
        </div>

        <div class="grid gap-3 md:grid-cols-4">
            <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">All</p>
                <p class="mt-1 text-2xl font-bold text-slate-800">{{ $totals['all'] ?? 0 }}</p>
            </div>
            <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">Admins</p>
                <p class="mt-1 text-2xl font-bold text-indigo-700">{{ $totals['admin'] ?? 0 }}</p>
            </div>
            <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">Operators</p>
                <p class="mt-1 text-2xl font-bold text-slate-700">{{ $totals['operator'] ?? 0 }}</p>
            </div>
            <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">Verified</p>
                <p class="mt-1 text-2xl font-bold text-emerald-700">{{ $totals['verified'] ?? 0 }}</p>
            </div>
        </div>

        <form method="GET" action="{{ route('admin.users.index') }}" class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
            <div class="grid gap-3 md:grid-cols-5">
                <div class="md:col-span-2">
                    <label class="text-xs font-semibold uppercase tracking-wide text-slate-400">Search</label>
                    <input type="text" name="q" value="{{ request('q') }}" placeholder="Name or email" class="mt-1 block w-full rounded-xl border-slate-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" />
                </div>
                <div>
                    <label class="text-xs font-semibold uppercase tracking-wide text-slate-400">Role</label>
                    <select name="role" class="mt-1 block w-full rounded-xl border-slate-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                        <option value="">All roles</option>
                        <option value="admin" @selected(request('role') === 'admin')>Admin</option>
                        <option value="operator" @selected(request('role') === 'operator')>Operator</option>
                    </select>
                </div>
                <div>
                    <label class="text-xs font-semibold uppercase tracking-wide text-slate-400">Status</label>
                    <select name="status" class="mt-1 block w-full rounded-xl border-slate-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                        <option value="">All statuses</option>
                        <option value="verified" @selected(request('status') === 'verified')>Verified</option>
                        <option value="unverified" @selected(request('status') === 'unverified')>Unverified</option>
                    </select>
                </div>
                <div>
                    <label class="text-xs font-semibold uppercase tracking-wide text-slate-400">Per page</label>
                    <select name="per_page" class="mt-1 block w-full rounded-xl border-slate-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                        @foreach([10, 12, 25, 50] as $size)
                            <option value="{{ $size }}" @selected((int) request('per_page', 12) === $size)>{{ $size }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="mt-4 flex flex-wrap gap-2">
                <button class="rounded-xl bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700" type="submit">Apply Filters</button>
                <a href="{{ route('admin.users.index') }}" class="rounded-xl border border-slate-200 px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">Reset</a>
            </div>
        </form>

        @if(session('success'))
            <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">{{ session('success') }}</div>
        @endif
        @if(session('error'))
            <div class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">{{ session('error') }}</div>
        @endif

        <form id="bulk-users-form" method="POST" action="{{ route('admin.users.bulk', request()->query()) }}" class="rounded-2xl border border-slate-200 bg-white shadow-sm">
            @csrf
            <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-200 px-5 py-4">
                <div class="flex items-center gap-3">
                    <label class="inline-flex items-center gap-2 text-sm font-semibold text-slate-700">
                        <input id="select-all-users" type="checkbox" class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500" />
                        Select all
                    </label>
                    <span class="text-sm text-slate-400">Bulk actions apply to selected users only.</span>
                </div>
                <div class="flex flex-wrap items-center gap-2">
                    <select name="action" class="rounded-xl border-slate-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500" required>
                        <option value="">Bulk action</option>
                        <option value="make_admin">Promote to admin</option>
                        <option value="make_operator">Set as operator</option>
                        <option value="delete">Delete</option>
                    </select>
                    <button type="submit" class="rounded-xl bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-800">Apply</button>
                </div>
            </div>

        </form>

        <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            <table class="min-w-full divide-y divide-slate-200">
                <thead class="bg-slate-50 text-left text-xs font-bold uppercase tracking-wide text-slate-500">
                    <tr>
                        <th class="w-12 px-5 py-3"></th>
                        <th class="px-5 py-3">Name</th>
                        <th class="px-5 py-3">Email</th>
                        <th class="px-5 py-3">Role</th>
                        <th class="px-5 py-3">Status</th>
                        <th class="px-5 py-3">Created</th>
                        <th class="px-5 py-3 text-right">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 text-sm">
                    @forelse($users as $user)
                        <tr class="hover:bg-slate-50/60">
                            <td class="px-5 py-3">
                                <input form="bulk-users-form" type="checkbox" name="user_ids[]" value="{{ $user->id }}" class="user-select rounded border-slate-300 text-indigo-600 focus:ring-indigo-500" />
                            </td>
                            <td class="px-5 py-3 font-semibold text-slate-800">{{ $user->name }}</td>
                            <td class="px-5 py-3 text-slate-600">{{ $user->email }}</td>
                            <td class="px-5 py-3">
                                <span class="rounded-full px-2.5 py-1 text-xs font-bold {{ $user->isAdmin() ? 'bg-indigo-100 text-indigo-700' : 'bg-slate-100 text-slate-700' }}">{{ $user->getRoleLabelAttribute() }}</span>
                            </td>
                            <td class="px-5 py-3 text-slate-600">{{ $user->email_verified_at ? 'Verified' : 'Unverified' }}</td>
                            <td class="px-5 py-3 text-slate-600">{{ $user->created_at?->format('d M Y') }}</td>
                            <td class="px-5 py-3">
                                <div class="flex justify-end gap-2">
                                    <a href="{{ route('admin.users.edit', $user) }}" class="rounded-lg border border-slate-200 px-3 py-1.5 text-xs font-semibold text-slate-700 hover:bg-slate-50">Edit</a>
                                    <form action="{{ route('admin.users.destroy', $user) }}" method="POST" onsubmit="return confirm('Delete this user?');">
                                        @csrf
                                        @method('DELETE')
                                        <button class="rounded-lg border border-rose-200 px-3 py-1.5 text-xs font-semibold text-rose-700 hover:bg-rose-50" type="submit">Delete</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-5 py-6 text-center text-sm text-slate-500">No users found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="text-sm text-slate-500">
            Showing {{ $users->firstItem() ?? 0 }} - {{ $users->lastItem() ?? 0 }} of {{ $users->total() }} users
        </div>

        <div>
            {{ $users->links() }}
        </div>
    </div>

    <script>
        (function () {
            const selectAll = document.getElementById('select-all-users');
            const selects = Array.from(document.querySelectorAll('.user-select'));
            if (selectAll) {
                selectAll.addEventListener('change', function () {
                    selects.forEach(function (checkbox) {
                        checkbox.checked = selectAll.checked;
                    });
                });
            }
        })();
    </script>
</x-app-layout>