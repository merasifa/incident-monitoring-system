<x-app-layout>
    <div class="space-y-5">
        <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <div class="flex items-center justify-between gap-3">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">History</p>
                    <h1 class="text-2xl font-bold text-slate-800">Incident History</h1>
                </div>
                <a href="{{ route('dashboard') }}" class="rounded-lg border border-slate-200 px-4 py-2 text-sm font-semibold text-slate-600 hover:bg-slate-50">Back to Dashboard</a>
            </div>
        </section>

        <section class="rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="divide-y divide-slate-100">
                @forelse ($activities as $activity)
                    <div class="flex items-start justify-between gap-4 p-4">
                        <div>
                            <p class="font-semibold text-slate-800">{{ $activity->description }}</p>
                            <p class="mt-1 text-xs text-slate-500">
                                {{ $activity->created_at->diffForHumans() }}
                                @if(!empty($activity->user_name)) • by {{ $activity->user_name }} @endif
                                @if(!empty($activity->incident_title)) • {{ $activity->incident_title }} @endif
                            </p>
                        </div>
                        <span class="rounded-full bg-slate-100 px-2.5 py-1 text-[11px] font-bold uppercase tracking-wide text-slate-500">
                            {{ $activity->event_type }}
                        </span>
                    </div>
                @empty
                    <div class="p-6 text-sm text-slate-500">No activity found.</div>
                @endforelse
            </div>
        </section>
    </div>
</x-app-layout>