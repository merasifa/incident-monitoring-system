<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <h2 class="text-xl font-semibold text-slate-900">Incident Management</h2>
            <div class="flex items-center gap-2">
                <a href="{{ route('incidents.workflow-board') }}" class="rounded-xl border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 shadow-sm hover:bg-slate-50">Workflow Board</a>
                <a href="{{ route('incidents.create') }}" class="rounded-xl bg-sky-700 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-sky-800">+ New Incident</a>
            </div>
        </div>
    </x-slot>

    <div class="mx-auto max-w-7xl space-y-5 px-4 py-8 sm:px-6 lg:px-8">
        @if (session('success'))
            <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-700">
                {{ session('success') }}
            </div>
        @endif

        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <form method="GET" class="grid gap-3 md:grid-cols-5" id="filter-form">
                <input id="incident-search" name="q" type="search" placeholder="Search by title, ID, category..." value="{{ request('q', '') }}" class="rounded-xl border-slate-300 text-sm focus:border-sky-500 focus:ring-sky-500 md:col-span-2 px-3 py-2" />
                <select name="severity" class="rounded-xl border-slate-300 text-sm focus:border-sky-500 focus:ring-sky-500">
                    <option value="">All Severity</option>
                    @foreach (['critical' => 'Critical', 'high' => 'High', 'medium' => 'Medium', 'low' => 'Low'] as $value => $label)
                        <option value="{{ $value }}" @selected(($filters['severity'] ?? '') === $value)>{{ $label }}</option>
                    @endforeach
                </select>

                <select name="status" class="rounded-xl border-slate-300 text-sm focus:border-sky-500 focus:ring-sky-500">
                    <option value="">All Status</option>
                    @foreach (['open' => 'Open', 'investigating' => 'Investigating', 'resolved' => 'Resolved'] as $value => $label)
                        <option value="{{ $value }}" @selected(($filters['status'] ?? '') === $value)>{{ $label }}</option>
                    @endforeach
                </select>

                <select name="category" class="rounded-xl border-slate-300 text-sm focus:border-sky-500 focus:ring-sky-500">
                    <option value="">All Category</option>
                    @foreach ($categories as $category)
                        <option value="{{ $category }}" @selected(($filters['category'] ?? '') === $category)>{{ $category }}</option>
                    @endforeach
                </select>

                <input name="date" type="date" value="{{ $filters['date'] ?? '' }}" class="rounded-xl border-slate-300 text-sm focus:border-sky-500 focus:ring-sky-500" />

                <div class="flex items-center gap-2">
                    <button type="submit" class="rounded-xl bg-slate-900 px-4 py-2 text-sm font-medium text-white hover:bg-slate-800">Filter</button>
                    <a href="{{ route('incidents.index') }}" class="rounded-xl border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">Reset</a>
                </div>
            </form>
        </div>

        <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 text-sm">
                    <thead class="bg-slate-50 text-xs uppercase tracking-wide text-slate-500">
                        <tr>
                            <th class="px-4 py-3 text-left">Incident</th>
                            <th class="px-4 py-3 text-left">Category</th>
                            <th class="px-4 py-3 text-left">Severity</th>
                            <th class="px-4 py-3 text-left">Status</th>
                            <th class="px-4 py-3 text-left">Created</th>
                            <th class="px-4 py-3 text-left">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse ($incidents as $incident)
                            <tr class="{{ $incident->severity === 'critical' ? 'bg-rose-50/60' : ($incident->severity === 'high' ? 'bg-orange-50/40' : ($incident->severity === 'medium' ? 'bg-amber-50/40' : 'bg-emerald-50/30')) }}">
                                <td class="px-4 py-3">
                                    <div class="font-semibold text-slate-900">{{ $incident->title }}</div>
                                    <div class="mt-1 flex flex-wrap items-center gap-2 text-xs">
                                        @if ((bool) $incident->is_urgent)
                                            <span class="rounded-full bg-rose-100 px-2 py-1 font-bold text-rose-700">URGENT</span>
                                        @endif
                                        @if ((bool) $incident->is_overdue)
                                            <span class="rounded-full bg-orange-100 px-2 py-1 font-bold text-orange-700">OVERDUE</span>
                                        @endif
                                    </div>
                                </td>
                                <td class="px-4 py-3 text-slate-700">{{ $incident->category }}</td>
                                <td class="px-4 py-3">
                                    <span class="rounded-full px-2 py-1 text-xs font-semibold {{ $incident->severity === 'critical' ? 'bg-rose-100 text-rose-700' : ($incident->severity === 'high' ? 'bg-orange-100 text-orange-700' : ($incident->severity === 'medium' ? 'bg-amber-100 text-amber-700' : 'bg-emerald-100 text-emerald-700')) }}">{{ $incident->severity_label }}</span>
                                </td>
                                <td class="px-4 py-3">
                                    <span class="rounded-full px-2 py-1 text-xs font-semibold bg-slate-100 text-slate-700">{{ $incident->status_label }}</span>
                                </td>
                                <td class="px-4 py-3 text-slate-600">{{ $incident->created_at->format('d M Y H:i') }}</td>
                                <td class="px-4 py-3">
                                    <div class="flex items-center gap-3">
                                        <a href="{{ route('incidents.show', ['incident' => $incident->id]) }}" class="text-sky-700 hover:text-sky-800">View</a>
                                        <a href="{{ route('incidents.edit', ['incident' => $incident->id]) }}" class="text-slate-700 hover:text-slate-900">Edit</a>
                                        @if(auth()->user()?->hasRole('admin'))
                                            <button type="button" data-action="{{ route('incidents.destroy', ['incident' => $incident->id]) }}" data-title="{{ e($incident->title) }}" class="delete-btn text-rose-700 hover:text-rose-800">Delete</button>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-4 py-8 text-center text-slate-500">No incidents found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="border-t border-slate-200 px-4 py-3">
                {{ $incidents->links() }}
            </div>
        </div>
    </div>
    <script>
        (function () {
            const input = document.getElementById('incident-search');
            const form = document.getElementById('filter-form');
            const tbody = document.querySelector('table tbody');
            const pager = document.querySelector('.border-t.border-slate-200');
            const url = '{{ route('api.incidents.search') }}';

            function serializeFilters() {
                const fd = new FormData(form);
                const params = new URLSearchParams();
                for (const [k, v] of fd.entries()) {
                    if (v) params.append(k, v);
                }
                return params;
            }

            function renderRow(item) {
                const created = item.created_at ? new Date(item.created_at).toLocaleString() : '';
                const severityClass = item.severity === 'critical' ? 'bg-rose-50/60' : (item.severity === 'high' ? 'bg-orange-50/40' : (item.severity === 'medium' ? 'bg-amber-50/40' : 'bg-emerald-50/30'));
                const urgent = item.is_urgent ? `<span class="rounded-full bg-rose-100 px-2 py-1 font-bold text-rose-700">URGENT</span>`: '';
                const overdue = item.is_overdue ? `<span class="rounded-full bg-orange-100 px-2 py-1 font-bold text-orange-700">OVERDUE</span>`: '';

                return `
                    <tr class="${severityClass}">
                        <td class="px-4 py-3">
                            <div class="font-semibold text-slate-900">${escapeHtml(item.title)}</div>
                            <div class="mt-1 flex flex-wrap items-center gap-2 text-xs">${urgent}${overdue}</div>
                        </td>
                        <td class="px-4 py-3 text-slate-700">${escapeHtml(item.category || '')}</td>
                        <td class="px-4 py-3"><span class="rounded-full px-2 py-1 text-xs font-semibold ${item.severity === 'critical' ? 'bg-rose-100 text-rose-700' : (item.severity === 'high' ? 'bg-orange-100 text-orange-700' : (item.severity === 'medium' ? 'bg-amber-100 text-amber-700' : 'bg-emerald-100 text-emerald-700'))}">${escapeHtml(item.severity_label || item.severity || '')}</span></td>
                        <td class="px-4 py-3 text-slate-700">${escapeHtml(item.status_label || item.status || '')}</td>
                        <td class="px-4 py-3 text-slate-600">${escapeHtml(created)}</td>
                        <td class="px-4 py-3">
                            <div class="flex items-center gap-3">
                                <a href="/incidents/${item.id}" class="text-sky-700 hover:text-sky-800">View</a>
                                <a href="/incidents/${item.id}/edit" class="text-slate-700 hover:text-slate-900">Edit</a>
                            </div>
                        </td>
                    </tr>
                `;
            }

            function escapeHtml(s) {
                if (!s) return '';
                return String(s).replace(/[&<>"]+/g, function (c) { return {'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;'}[c]; });
            }

            let timer = null;
            input.addEventListener('input', function (e) {
                clearTimeout(timer);
                timer = setTimeout(async function () {
                    const q = input.value.trim();
                    if (!q) {
                        // fall back to full page reload to preserve server-side filters
                        window.location = window.location.pathname + '?' + serializeFilters().toString();
                        return;
                    }

                    const params = serializeFilters();
                    params.set('q', q);

                            try {
                                const res = await fetch(url + '?' + params.toString(), { credentials: 'same-origin', headers: { 'Accept': 'application/json' } });
                                if (!res.ok) return;
                                const json = await res.json();
                                const rows = (json.data || []);
                                if (tbody) tbody.innerHTML = rows.map(renderRow).join('');
                                if (pager) {
                                    pager.innerHTML = renderPagination(json);
                                    pager.style.display = '';
                                }
                            } catch (err) {
                                console.error(err);
                            }
                }, 300);
            });

                    async function fetchPage(page) {
                        clearTimeout(timer);
                        const params = serializeFilters();
                        params.set('q', input.value.trim());
                        params.set('page', page);
                        try {
                            const res = await fetch(url + '?' + params.toString(), { credentials: 'same-origin', headers: { 'Accept': 'application/json' } });
                            if (!res.ok) return;
                            const json = await res.json();
                            const rows = (json.data || []);
                            if (tbody) tbody.innerHTML = rows.map(renderRow).join('');
                            if (pager) {
                                pager.innerHTML = renderPagination(json);
                                pager.style.display = '';
                            }
                            // scroll to top of table
                            window.scrollTo({ top: document.querySelector('table').offsetTop - 80, behavior: 'smooth' });
                        } catch (err) { console.error(err); }
                    }

                    function renderPagination(json) {
                        const current = json.current_page || 1;
                        const last = json.last_page || 1;
                        const total = json.total || 0;
                        const per = json.per_page || 12;

                        if (last <= 1) return `<div class="px-4 py-3 text-sm text-slate-500">${total} results</div>`;

                        const pages = [];
                        const start = Math.max(1, current - 2);
                        const end = Math.min(last, current + 2);

                        pages.push(`<div class="flex items-center justify-between">`);
                        pages.push(`<div class="text-sm text-slate-500">${total} results</div>`);
                        pages.push(`<div class="flex items-center gap-2">`);

                        pages.push(`<button class="rounded px-3 py-1 border" ${current === 1 ? 'disabled' : ''} onclick="(function(){window.__fetchPage(${current - 1});})();">Prev</button>`);

                        for (let p = start; p <= end; p++) {
                            if (p === current) {
                                pages.push(`<button class="rounded px-3 py-1 bg-slate-900 text-white" disabled>${p}</button>`);
                            } else {
                                pages.push(`<button class="rounded px-3 py-1 border" onclick="(function(){window.__fetchPage(${p});})();">${p}</button>`);
                            }
                        }

                        pages.push(`<button class="rounded px-3 py-1 border" ${current === last ? 'disabled' : ''} onclick="(function(){window.__fetchPage(${current + 1});})();">Next</button>`);
                        pages.push(`</div></div>`);

                        return pages.join('');
                    }
            
                // Delete modal handling (admin only)
                document.addEventListener('DOMContentLoaded', function () {
                    const modal = document.createElement('div');
                    modal.id = 'delete-modal';
                    modal.innerHTML = `
                        <div class="fixed inset-0 z-50 hidden flex items-center justify-center px-4" aria-hidden="true">
                            <div class="absolute inset-0 bg-black/40"></div>
                            <div class="relative w-full max-w-md rounded-2xl bg-white p-6 shadow-lg">
                                <h3 class="text-lg font-semibold text-slate-900">Confirm delete</h3>
                                <p id="delete-modal-title" class="mt-2 text-sm text-slate-600">Are you sure you want to delete this incident?</p>
                                <div class="mt-4 flex justify-end gap-2">
                                    <button id="delete-modal-cancel" class="rounded px-3 py-2 border">Cancel</button>
                                    <form id="delete-modal-form" method="POST" action="" class="inline">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="rounded bg-rose-600 px-3 py-2 text-white">Delete</button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    `;
                    document.body.appendChild(modal);

                    function openModal(action, title) {
                        const wrap = document.querySelector('#delete-modal > div');
                        if (!wrap) return;
                        wrap.classList.remove('hidden');
                        const form = document.getElementById('delete-modal-form');
                        form.action = action;
                        const titleEl = document.getElementById('delete-modal-title');
                        titleEl.textContent = `Are you sure you want to delete "${title}"?`;
                    }

                    function closeModal() {
                        const wrap = document.querySelector('#delete-modal > div');
                        if (!wrap) return;
                        wrap.classList.add('hidden');
                    }

                    document.body.addEventListener('click', function (e) {
                        const btn = e.target.closest('.delete-btn');
                        if (btn) {
                            e.preventDefault();
                            const action = btn.getAttribute('data-action');
                            const title = btn.getAttribute('data-title') || '';
                            openModal(action, title);
                        }
                    });

                    document.body.addEventListener('click', function (e) {
                        if (e.target && e.target.id === 'delete-modal-cancel') {
                            e.preventDefault();
                            closeModal();
                        }
                    });
                });

                    // expose fetchPage for inline onclick handlers in generated HTML
                    window.__fetchPage = fetchPage;
        })();
    </script>
</x-app-layout>
