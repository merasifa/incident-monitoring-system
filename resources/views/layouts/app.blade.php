<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Operational Attention Monitoring System') }}</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans antialiased">
        <div class="min-h-screen bg-[#f2f4f8]">
            <div class="mx-auto flex min-h-screen max-w-[1600px]">
                @include('layouts.navigation')

                <div class="flex min-w-0 flex-1 flex-col">
                    <!-- Top navbar: search, status, help, notifications, user -->
                    <div class="nav-surface nav-accent-line sticky top-0 z-40 border-b border-indigo-100/80 px-6 py-3 backdrop-blur-xl lg:px-8 bg-gradient-to-r from-white via-indigo-50/90 to-violet-50/80">
                        <div class="flex items-center justify-between gap-4">
                            <div class="flex items-center gap-4">
                                {{-- Operational hub removed per design preference --}}
                            </div>

                            <div class="flex items-center gap-3">
                                <div class="relative">
                                    <button id="notification-button" title="Notifications" class="relative inline-flex items-center rounded-lg border border-indigo-100/80 bg-gradient-to-r from-white to-indigo-50/80 px-3 py-2 text-sm font-semibold text-slate-700 shadow-sm transition hover:-translate-y-0.5 hover:border-indigo-200 hover:bg-indigo-100/70 hover:text-indigo-800">
                                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6 6 0 10-12 0v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                                        </svg>
                                        <span id="notification-count" class="absolute -top-1 -right-1 hidden inline-flex h-5 w-5 items-center justify-center rounded-full bg-rose-600 text-xs font-bold text-white">0</span>
                                    </button>

                                    <div id="notification-dropdown" class="hidden absolute right-0 mt-2 w-80 rounded-2xl border border-slate-200/80 bg-white shadow-[0_20px_40px_rgba(15,23,42,0.12)]">
                                        <div class="px-4 py-3 border-b border-slate-100 text-sm font-semibold text-slate-700">Notifications</div>
                                        <div id="notification-list" class="max-h-64 overflow-auto"></div>
                                        <div class="px-3 py-2 border-t border-slate-100 text-right">
                                            <a href="{{ route('incidents.index') }}" class="text-sm font-medium text-indigo-700 hover:text-indigo-800">View all incidents</a>
                                        </div>
                                    </div>
                                </div>

                                <div class="flex items-center gap-3">
                                    <div class="grid h-9 w-9 place-items-center rounded-full bg-gradient-to-br from-indigo-500 via-indigo-600 to-violet-500 text-white shadow-sm ring-2 ring-indigo-100/90">T</div>
                                    <div class="text-sm text-slate-700">{{ auth()->user()->name ?? 'User' }}<div class="text-xs text-slate-400">{{ auth()->user()?->getRoleLabelAttribute() ?? '' }}</div></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    @isset($header)
                        <header class="bg-transparent px-6 py-4 lg:px-8">
                            {{ $header }}
                        </header>
                    @endisset

                    <script>
                        (function () {
                            const badge = document.getElementById('notification-count');
                            const btn = document.getElementById('notification-button');
                            const url = '{{ route('api.notifications') }}';
                            let lastJson = null;

                            async function refresh() {
                                try {
                                    const res = await fetch(url, { credentials: 'same-origin', headers: { 'Accept': 'application/json' } });
                                    if (!res.ok) return;
                                    const json = await res.json();
                                    lastJson = json;
                                    const total = (json.overdue_count || 0) + (json.new_critical_count || 0) + (json.due_soon_count || 0);
                                    if (!badge) return;
                                    if (total > 0) {
                                        badge.textContent = total;
                                        badge.classList.remove('hidden');
                                    } else {
                                        badge.classList.add('hidden');
                                    }
                                    badge.title = `${json.overdue_count || 0} overdue • ${json.due_soon_count || 0} due soon • ${json.new_critical_count || 0} new critical`;
                                } catch (err) {
                                    console.error('notif refresh', err);
                                }
                            }

                            function escapeHtml(s) {
                                if (!s) return '';
                                return String(s).replace(/[&<>\"]+/g, function (c) { return {'&':'&amp;','<':'&lt;','>':'&gt;','\"':'&quot;'}[c]; });
                            }

                            function renderDropdown(json) {
                                const list = document.getElementById('notification-list');
                                if (!list) return;
                                const items = [];

                                const pushItem = (it, label) => {
                                    const read = it.read ? 'opacity-60' : '';
                                    items.push(`
                                        <div class="px-3 py-2 hover:bg-slate-50 ${read} border-b border-slate-100">
                                            <div class="flex items-start justify-between gap-2">
                                                <div class="flex-1">
                                                    <a href="/incidents/${it.id}" class="block text-sm font-semibold text-slate-900">${escapeHtml(it.title)}</a>
                                                    <div class="mt-1 text-xs text-slate-500">${escapeHtml(label)} • ${escapeHtml(it.category || '')}</div>
                                                </div>
                                                <div class="ml-2 flex-shrink-0 text-right">
                                                    <div class="text-xs text-slate-400">${escapeHtml(it.remaining_time_label || '')}</div>
                                                    <button data-incident-id="${it.id}" class="mark-read mt-2 inline-block rounded bg-slate-100 px-2 py-1 text-xs font-medium">Mark</button>
                                                </div>
                                            </div>
                                        </div>
                                    `);
                                };

                                (json.top_overdue || []).forEach(it => pushItem(it, 'Overdue'));
                                (json.top_critical || []).forEach(it => pushItem(it, 'Critical'));

                                if (!items.length) {
                                    list.innerHTML = '<div class="px-4 py-4 text-sm text-slate-500">No notifications</div>';
                                } else {
                                    list.innerHTML = items.join('');
                                }
                            }

                            // initial fetch
                            refresh();
                            setInterval(refresh, 30000);

                            const dropdown = document.getElementById('notification-dropdown');
                            const csrf = '{{ csrf_token() }}';

                            if (btn) btn.addEventListener('click', function (e) {
                                e.stopPropagation();
                                if (!dropdown) return;
                                dropdown.classList.toggle('hidden');
                                if (!dropdown.classList.contains('hidden') && lastJson) {
                                    renderDropdown(lastJson);
                                }
                            });

                            document.addEventListener('click', function (e) {
                                if (!dropdown) return;
                                if (!dropdown.classList.contains('hidden')) {
                                    dropdown.classList.add('hidden');
                                }
                            });

                            document.addEventListener('click', async function (e) {
                                const target = e.target;
                                if (!target) return;
                                const btnMark = target.closest && target.closest('.mark-read');
                                if (!btnMark) return;
                                e.preventDefault();
                                const incidentId = btnMark.getAttribute('data-incident-id');
                                if (!incidentId) return;

                                try {
                                    const res = await fetch('{{ route('api.notifications.read') }}', {
                                        method: 'POST',
                                        credentials: 'same-origin',
                                        headers: {
                                            'Accept': 'application/json',
                                            'Content-Type': 'application/json',
                                            'X-CSRF-TOKEN': csrf
                                        },
                                        body: JSON.stringify({ incident_id: parseInt(incidentId, 10) })
                                    });

                                    if (!res.ok) throw new Error('fail');
                                    await refresh();
                                    if (lastJson && dropdown && !dropdown.classList.contains('hidden')) renderDropdown(lastJson);
                                } catch (err) {
                                    console.error('mark read failed', err);
                                }
                            });

                            // navbar is intentionally consistent across pages; no search field here
                        })();
                    </script>

                    <main class="min-w-0 flex-1 px-6 py-6 lg:px-8">
                        {{ $slot }}
                    </main>
                </div>
            </div>
        </div>
    </body>
</html>
