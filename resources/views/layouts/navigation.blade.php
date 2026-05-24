<aside class="sidebar-surface sticky top-0 hidden h-screen w-64 shrink-0 overflow-y-auto border-r border-indigo-100/80 md:flex md:flex-col">
    <div class="px-5 pb-6 pt-7">
        <a href="{{ route('dashboard') }}" class="flex items-center gap-3 rounded-2xl border border-indigo-100/80 bg-gradient-to-r from-white via-indigo-50/90 to-violet-50/80 px-3 py-2 shadow-sm backdrop-blur">
            <div class="grid h-9 w-9 place-items-center rounded-xl bg-gradient-to-br from-indigo-500 via-indigo-600 to-violet-500 text-white shadow-sm ring-2 ring-indigo-100/90">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M3 13h4v8H3zM10 3h4v18h-4zM17 9h4v12h-4z" />
                </svg>
            </div>
            <div>
                <p class="text-xl font-bold tracking-tight text-indigo-700">OIMS</p>
                <p class="text-xs font-medium text-slate-500">Operational Monitor</p>
            </div>
        </a>
    </div>

    <nav class="space-y-1 px-3">
        <a href="{{ route('dashboard') }}" class="group flex items-center gap-3 rounded-xl border px-3 py-2 text-sm font-semibold transition {{ request()->routeIs('dashboard') ? 'border-indigo-200/80 bg-gradient-to-r from-indigo-50 via-indigo-100 to-violet-50 text-indigo-800 nav-pill-active' : 'border-transparent text-slate-700 hover:border-indigo-100 hover:bg-white/80 hover:text-indigo-700' }}">
            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M4 5h16v6H4zM4 13h6v6H4zM14 13h6v6h-6z" />
            </svg>
            Dashboard
        </a>

        @if(auth()->user()?->hasRole('admin'))
            <a href="{{ route('admin.users.index') }}" class="group flex items-center gap-3 rounded-xl border px-3 py-2 text-sm font-semibold transition {{ request()->routeIs('admin.users.*') ? 'border-indigo-200/80 bg-gradient-to-r from-indigo-50 via-indigo-100 to-violet-50 text-indigo-800 nav-pill-active' : 'border-transparent text-slate-700 hover:border-indigo-100 hover:bg-white/80 hover:text-indigo-700' }}">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M17 20h5v-2a4 4 0 00-4-4h-1m-4 6H2v-2a4 4 0 014-4h7m6-4a3 3 0 11-6 0 3 3 0 016 0zM10 7a3 3 0 11-6 0 3 3 0 016 0z" />
                </svg>
                Users
            </a>
        @endif

        <a href="{{ route('incidents.index') }}" class="group flex items-center gap-3 rounded-xl border px-3 py-2 text-sm font-semibold transition {{ request()->routeIs('incidents.index', 'incidents.create', 'incidents.show', 'incidents.edit') ? 'border-indigo-200/80 bg-gradient-to-r from-indigo-50 via-indigo-100 to-violet-50 text-indigo-800 nav-pill-active' : 'border-transparent text-slate-700 hover:border-indigo-100 hover:bg-white/80 hover:text-indigo-700' }}">
            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 9v4m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z" />
            </svg>
            Incidents
        </a>

            <a href="{{ route('history.index') }}" class="group flex items-center gap-3 rounded-xl border px-3 py-2 text-sm font-semibold transition {{ request()->routeIs('history.*') ? 'border-indigo-200/80 bg-gradient-to-r from-indigo-50 via-indigo-100 to-violet-50 text-indigo-800 nav-pill-active' : 'border-transparent text-slate-700 hover:border-indigo-100 hover:bg-white/80 hover:text-indigo-700' }}">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 8v4l3 3m7-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                History
            </a>

        <a href="{{ route('incidents.workflow-board') }}" class="group flex items-center gap-3 rounded-xl border px-3 py-2 text-sm font-semibold transition {{ request()->routeIs('incidents.workflow-board') ? 'border-indigo-200/80 bg-gradient-to-r from-indigo-50 via-indigo-100 to-violet-50 text-indigo-800 nav-pill-active' : 'border-transparent text-slate-700 hover:border-indigo-100 hover:bg-white/80 hover:text-indigo-700' }}">
            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M4 6h6v12H4zM14 6h6v8h-6zM14 16h6v2h-6z" />
            </svg>
            Workflow Board
        </a>

        <a href="{{ route('profile.edit') }}" class="group flex items-center gap-3 rounded-xl border px-3 py-2 text-sm font-semibold transition {{ request()->routeIs('profile.*') ? 'border-indigo-200/80 bg-gradient-to-r from-indigo-50 via-indigo-100 to-violet-50 text-indigo-800 nav-pill-active' : 'border-transparent text-slate-700 hover:border-indigo-100 hover:bg-white/80 hover:text-indigo-700' }}">
            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M11.983 5.333A2.667 2.667 0 1011.983 10.667 2.667 2.667 0 0011.983 5.333zM4 12a8 8 0 1115.465 2.665M4 12h2.4m13.2 0H22M8.4 20h7.2" />
            </svg>
            Settings
        </a>
    </nav>

    <div class="mt-auto border-t border-indigo-100/80 bg-gradient-to-t from-indigo-50/50 to-transparent p-3">
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="flex w-full items-center gap-3 rounded-xl border border-transparent px-3 py-2 text-left text-sm font-semibold text-slate-700 transition hover:border-indigo-100 hover:bg-white/90 hover:text-indigo-700">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M17 16l4-4m0 0l-4-4m4 4H9m4 8H5a2 2 0 01-2-2V6a2 2 0 012-2h8" />
                </svg>
                Logout
            </button>
        </form>
    </div>
</aside>
