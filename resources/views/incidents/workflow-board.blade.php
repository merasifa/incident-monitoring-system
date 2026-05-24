<x-app-layout>
    <x-slot name="header">
            <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h2 class="text-xl font-semibold text-slate-900">Incident Workflow Board</h2>
                <p class="text-sm text-slate-500">Operational view for tracking incident progress across the live workflow.</p>
            </div>
        </div>
    </x-slot>

    @php
        $columns = [
            'open' => [
                'label' => 'Open',
                'tone' => 'slate',
                'surface' => 'from-slate-50 to-white',
                'border' => 'border-slate-200',
                'accent' => 'bg-slate-100 text-slate-700',
                'empty' => 'No incidents waiting in Open.',
            ],
            'investigating' => [
                'label' => 'Investigating',
                'tone' => 'amber',
                'surface' => 'from-amber-50 to-white',
                'border' => 'border-amber-200',
                'accent' => 'bg-amber-100 text-amber-700',
                'empty' => 'No incidents currently under investigation.',
            ],
            'resolved' => [
                'label' => 'Resolved',
                'tone' => 'emerald',
                'surface' => 'from-emerald-50 to-white',
                'border' => 'border-emerald-200',
                'accent' => 'bg-emerald-100 text-emerald-700',
                'empty' => 'No resolved incidents yet.',
            ],
        ];

        $boardCounts = collect($columns)->mapWithKeys(function ($column, $status) use ($board) {
            return [$status => $board->get($status, collect())->count()];
        });

        $totalIncidents = $board->flatten(1)->count();
    @endphp

    <div class="mx-auto max-w-7xl space-y-6 px-4 py-8 sm:px-6 lg:px-8">
        <section class="grid gap-4 md:grid-cols-3">
            <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">Total on board</p>
                <p class="mt-2 text-4xl font-bold text-slate-900">{{ $totalIncidents }}</p>
            </article>
            <article class="rounded-2xl border border-amber-200 bg-white p-5 shadow-sm">
                <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">In progress</p>
                <p class="mt-2 text-4xl font-bold text-amber-700">{{ $boardCounts['investigating'] }}</p>
            </article>
            <article class="rounded-2xl border border-emerald-200 bg-white p-5 shadow-sm">
                <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">Resolved</p>
                <p class="mt-2 text-4xl font-bold text-emerald-700">{{ $boardCounts['resolved'] }}</p>
            </article>
        </section>

        <section class="grid gap-4 xl:grid-cols-2">
            @foreach (['open','investigating'] as $status)
                @php $column = $columns[$status]; @endphp
                <div class="rounded-3xl border {{ $column['border'] }} bg-gradient-to-b {{ $column['surface'] }} p-4 shadow-sm">
                    <div class="mb-4 flex items-center justify-between gap-3">
                        <div>
                            <h3 class="text-lg font-bold text-slate-900">{{ $column['label'] }}</h3>
                            <p class="text-xs uppercase tracking-wide text-slate-400">{{ $boardCounts[$status] }} incidents</p>
                        </div>
                        <span class="rounded-full px-3 py-1 text-xs font-semibold {{ $column['accent'] }}">{{ strtoupper($status) }}</span>
                    </div>

                    <div class="space-y-3">
                        @forelse ($board->get($status, collect()) as $incident)
                            @php
                                $toneMap = [
                                    'red' => 'border-rose-200 bg-rose-50 text-rose-700',
                                    'orange' => 'border-orange-200 bg-orange-50 text-orange-700',
                                    'green' => 'border-emerald-200 bg-emerald-50 text-emerald-700',
                                    'slate' => 'border-slate-200 bg-white text-slate-700',
                                ];
                                $toneClass = $toneMap[$incident->timeline_tone] ?? $toneMap['slate'];
                            @endphp

                            @php
                                $leftTone = match($incident->timeline_tone) {
                                    'red' => 'border-rose-400',
                                    'orange' => 'border-amber-400',
                                    'green' => 'border-emerald-400',
                                    default => 'border-slate-200',
                                };
                            @endphp

                            <article class="rounded-2xl border border-white bg-white/90 p-4 shadow-sm ring-1 ring-slate-100 transition hover:-translate-y-0.5 hover:shadow-md border-l-4 {{ $leftTone }}" data-due-at="{{ $incident->due_at?->toIsoString() }}" data-remaining-selector="#remaining-{{ $incident->id }}">
                                <div class="flex items-start justify-between gap-3">
                                    <div class="min-w-0">
                                        <h4 class="truncate text-sm font-semibold text-slate-900">{{ $incident->title }}</h4>
                                        <p class="mt-1 text-xs text-slate-500">{{ $incident->category }}</p>
                                    </div>
                                    <span class="shrink-0 rounded-full px-2.5 py-1 text-[11px] font-bold {{ $incident->badge_color }}">{{ $incident->badge }}</span>
                                </div>

                                <div class="mt-4 space-y-2 text-sm">
                                    <div class="flex items-center justify-between gap-3">
                                        <span class="text-slate-500">Due date</span>
                                        <span class="font-medium text-slate-800">{{ $incident->due_at?->format('d M Y H:i') ?? 'No due date' }}</span>
                                    </div>
                                    <div class="flex items-center justify-between gap-3">
                                        <span class="text-slate-500">Remaining time</span>
                                        <span id="remaining-{{ $incident->id }}" class="rounded-full border px-2.5 py-1 text-xs font-semibold {{ $toneClass }}">{{ $incident->remaining_time_label }}</span>
                                    </div>
                                </div>

                                <div class="mt-4 flex items-center justify-between gap-3 border-t border-slate-100 pt-3 text-xs text-slate-500">
                                    <span>INC-{{ str_pad((string) $incident->id, 4, '0', STR_PAD_LEFT) }}</span>
                                    @if ($incident->timeline_state === 'overdue')
                                        <span class="font-semibold text-rose-700">Overdue</span>
                                    @elseif ($incident->timeline_state === 'due_soon')
                                        <span class="font-semibold text-orange-700">Due soon</span>
                                    @elseif ($incident->timeline_state === 'resolved')
                                        <span class="font-semibold text-emerald-700">Resolved</span>
                                    @else
                                        <span class="font-semibold text-slate-600">On track</span>
                                    @endif
                                </div>
                            </article>
                        @empty
                            <div class="rounded-2xl border border-dashed border-slate-200 bg-white/70 px-4 py-8 text-center text-sm text-slate-500">
                                {{ $column['empty'] }}
                            </div>
                        @endforelse
                    </div>
                </div>
            @endforeach
        </section>

        <section class="mt-4 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
            <div class="flex items-center justify-between">
                <div>
                    <h3 class="text-sm font-semibold text-slate-900">Recently Resolved</h3>
                    <p class="text-xs text-slate-500">Latest resolved incidents (showing {{ $board->get('recent_resolved')->count() }} of {{ $board->get('resolved_count') }})</p>
                </div>
                <a href="{{ route('incidents.index', ['status' => 'resolved']) }}" class="text-sm font-medium text-sky-700">View All Resolved</a>
            </div>

            <div class="mt-3 grid gap-3">
                @forelse($board->get('recent_resolved', collect()) as $r)
                    <div class="flex items-center justify-between rounded-lg border border-slate-100 px-3 py-2">
                        <div>
                            <a href="{{ route('incidents.show', ['incident' => $r->id]) }}" class="font-semibold text-slate-900">{{ $r->title }}</a>
                            <div class="text-xs text-slate-500">Resolved {{ $r->resolved_at?->diffForHumans() }}</div>
                        </div>
                        <div class="text-xs text-slate-500">INC-{{ str_pad((string) $r->id, 4, '0', STR_PAD_LEFT) }}</div>
                    </div>
                @empty
                    <div class="text-sm text-slate-500">No recently resolved incidents.</div>
                @endforelse
            </div>
        </section>
    </div>

    <script>
        (function () {
            // update remaining time labels every 30s
            function humanRemaining(dueIso) {
                if (!dueIso) return 'No due date';
                const due = new Date(dueIso);
                const now = new Date();
                const diffMs = due - now;
                const diffH = Math.floor(diffMs / (1000 * 60 * 60));
                const absH = Math.abs(diffH);

                if (diffMs < 0) {
                    if (absH < 24) return `Overdue by ${absH}h`;
                    const d = Math.ceil(absH / 24);
                    return `Overdue by ${d}d`;
                }

                if (absH < 1) return 'Due now';
                if (absH < 24) return `Due in ${absH}h`;
                const d = Math.ceil(absH / 24);
                return `Due in ${d}d`;
            }

            function updateAll() {
                document.querySelectorAll('[data-due-at]').forEach(function (el) {
                    const iso = el.getAttribute('data-due-at');
                    const sel = el.getAttribute('data-remaining-selector');
                    if (!sel) return;
                    const target = document.querySelector(sel);
                    if (!target) return;
                    target.textContent = humanRemaining(iso);
                });
            }

            updateAll();
            setInterval(updateAll, 30000);
        })();
    </script>
</x-app-layout>