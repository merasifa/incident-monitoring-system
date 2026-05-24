<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="text-xl font-semibold text-slate-900">Incident Detail</h2>
            {{-- actions moved into the incident card for clearer UI --}}
        </div>
    </x-slot>

    <div class="mx-auto max-w-6xl space-y-6 px-4 py-10 sm:px-6 lg:px-8">
        <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <div class="mb-4 flex items-start justify-between gap-4">
                <div class="flex flex-wrap items-center gap-2">
                <span class="rounded-full px-3 py-1 text-xs font-semibold {{ $incident->severity === 'critical' ? 'bg-rose-100 text-rose-700' : ($incident->severity === 'high' ? 'bg-orange-100 text-orange-700' : ($incident->severity === 'medium' ? 'bg-amber-100 text-amber-700' : 'bg-emerald-100 text-emerald-700')) }}">{{ $incident->severity_label }}</span>
                <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-700">{{ $incident->status_label }}</span>
                @if ((bool) $incident->is_urgent)
                    <span class="rounded-full bg-rose-50 px-3 py-1 text-xs font-bold text-rose-700">URGENT</span>
                @endif
                @if ((bool) $incident->is_overdue)
                    <span class="rounded-full bg-orange-50 px-3 py-1 text-xs font-bold text-orange-700">OVERDUE</span>
                @endif
                </div>

                <div class="flex items-center gap-2">
                    @if($incident->status !== 'resolved')
                        <a href="{{ route('incidents.edit', ['incident' => $incident->id]) }}" class="rounded-xl bg-indigo-600 px-3 py-1 text-sm font-semibold text-white hover:bg-indigo-700 shadow-sm">Update Status</a>
                    @else
                        <span class="rounded-md bg-emerald-50 px-3 py-1 text-sm font-semibold text-emerald-700">Resolved</span>
                    @endif
                    <a href="{{ route('incidents.index') }}" class="text-sm font-medium text-sky-700 hover:text-sky-800">Back</a>
                </div>
            </div>

            <h3 class="text-2xl font-semibold text-slate-900">{{ $incident->title }}</h3>
            <p class="mt-2 text-slate-600">{{ $incident->description ?: 'No description.' }}</p>

            <dl class="mt-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <div>
                    <dt class="text-xs font-medium uppercase tracking-wide text-slate-500">Category</dt>
                    <dd class="mt-1 text-sm font-semibold text-slate-800">{{ $incident->category }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-medium uppercase tracking-wide text-slate-500">Created By</dt>
                    <dd class="mt-1 text-sm font-semibold text-slate-800">{{ $incident->created_by_name ?? 'System' }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-medium uppercase tracking-wide text-slate-500">Assigned To</dt>
                    <dd class="mt-1 text-sm font-semibold text-slate-800">{{ $incident->assignee_name ?? 'Unassigned' }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-medium uppercase tracking-wide text-slate-500">Created At</dt>
                    <dd class="mt-1 text-sm font-semibold text-slate-800">{{ $incident->created_at->format('d M Y H:i') }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-medium uppercase tracking-wide text-slate-500">Due Date</dt>
                    <dd class="mt-1 text-sm font-semibold text-slate-800">{{ $incident->due_at?->format('d M Y H:i') ?? '-' }}</dd>
                </div>
            </dl>
        </div>

        <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <h4 class="text-lg font-semibold text-slate-900">Recent Activity</h4>
            @php
                $statusEvents = $activities->filter(fn($a) => in_array($a->event_type, ['incident_resolved', 'incident_investigating', 'status_changed']))->values();
            @endphp

            @if($statusEvents->isNotEmpty())
                <div class="mb-4 grid gap-2">
                    @foreach($statusEvents as $ev)
                        <div class="flex items-center gap-3 rounded-lg bg-slate-50 p-3">
                            <div class="text-xs font-semibold text-slate-500">{{ $ev->created_at->format('d M Y H:i') }}</div>
                            <div class="text-sm font-medium text-slate-800">{{ $ev->description }}</div>
                            <div class="ml-auto text-xs text-slate-500">{{ $ev->user_name ?? 'System' }}</div>
                        </div>
                    @endforeach
                </div>
            @endif

            <div class="mt-4 space-y-3">
                @forelse ($activities as $activity)
                    <div class="rounded-xl bg-slate-50 p-3">
                        <p class="text-sm font-medium text-slate-800">{{ $activity->description }}</p>
                        <p class="mt-1 text-xs text-slate-500">{{ $activity->created_at->diffForHumans() }} • by {{ $activity->user_name ?? 'System' }}</p>
                    </div>
                @empty
                    <p class="text-sm text-slate-500">No activity yet.</p>
                @endforelse
            </div>
        </div>
    </div>
</x-app-layout>
