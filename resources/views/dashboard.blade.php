<x-app-layout>
    {{-- header intentionally empty: top navbar is global in layouts/app.blade.php --}}

    <div class="space-y-5">
        @php
            $isCritical = $summary->critical_open > 0;
            $hasOverdue = ($summary->overdue_count ?? 0) > 0;
            $hasDueSoon = ($summary->due_soon_count ?? 0) > 0;
            $hasInvestigating = ($summary->investigating ?? 0) > 0;
            $hasOpenUrgent = ($summary->open_urgent ?? 0) > 0;

            $monitoringState = 'normal';

            if ($isCritical) {
                $monitoringState = 'critical';
            } elseif ($hasOverdue || $hasDueSoon || $hasInvestigating || $hasOpenUrgent) {
                $monitoringState = 'warning';
            }
        @endphp

        @if($monitoringState === 'critical')
            <section class="rounded-2xl border border-rose-200 bg-gradient-to-r from-rose-50 to-orange-50 px-6 py-4 shadow-sm">
                <div class="mb-4 flex items-center justify-between gap-3">
                    <div>
                        <h2 class="text-3xl font-bold text-slate-800">🚨 Critical Incident Detected</h2>
                        <p class="text-sm text-slate-600">Immediate operational response required</p>
                    </div>
                    <a href="{{ route('incidents.index', ['severity' => 'critical']) }}" class="rounded-xl bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-700">View Alert Hub</a>
                </div>
            </section>

        @elseif($monitoringState === 'warning')
            <section class="rounded-2xl border border-amber-200 bg-gradient-to-r from-amber-50 to-white px-6 py-4 shadow-sm">
                <div class="mb-4 flex items-center justify-between gap-3">
                    <div>
                        <h2 class="text-3xl font-bold text-slate-800">⚠ Operational Attention Required</h2>
                        <p class="text-sm text-slate-600">Incident requires immediate action</p>
                    </div>
                    <a href="{{ route('incidents.index') }}" class="rounded-xl bg-amber-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-amber-700">View Incidents</a>
                </div>
            </section>

        @else
            <section class="rounded-2xl border border-emerald-200 bg-gradient-to-r from-emerald-50 to-white px-6 py-5 shadow-sm">
                <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
                    <div>
                        <div class="mb-2 inline-flex rounded-full bg-emerald-100 px-3 py-1 text-[11px] font-bold uppercase tracking-wide text-emerald-700">Monitoring clear</div>
                        <h2 class="text-3xl font-bold text-slate-800">✅ No Critical Incident Detected</h2>
                        <p class="mt-1 text-sm text-slate-600">System Operating Normally</p>
                    </div>
                    <div class="rounded-2xl border border-emerald-200 bg-white px-4 py-3 text-sm text-slate-600 shadow-sm">
                        <p class="font-semibold text-emerald-700">Stable operations</p>
                        <p class="mt-1">All systems are operating within acceptable thresholds.</p>
                    </div>
                </div>
            </section>
        @endif

        @php
            // derive a short priority alert list: top items from the already-sorted incidents
            $priorityItems = $incidents->filter(function($it) {
                return in_array($it->badge, ['CRITICAL', 'OVERDUE', 'DUE SOON', 'DEADLINE']) || ($it->status === 'open' && $it->due_category !== 'none');
            })->take(4);
        @endphp

        @if($priorityItems->isNotEmpty())
            <section class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                <div class="mb-3 flex items-center justify-between">
                    <h3 class="text-xl font-bold text-slate-800">Priority Alerts</h3>
                    <a href="{{ route('incidents.index') }}" class="text-sm font-medium text-indigo-600">View all priorities</a>
                </div>

                <div class="space-y-3">
                    @foreach($priorityItems as $it)
                        <div class="flex items-center justify-between gap-3 rounded-xl border border-slate-100 bg-slate-50 p-3">
                            <div>
                                <div class="mb-1 flex items-center gap-2">
                                    <span class="rounded-full px-2 py-0.5 text-[11px] font-bold {{ $it->badge_color }}">{{ $it->badge }}</span>
                                    <span class="text-xs font-medium text-slate-500">INC-{{ str_pad((string) $it->id, 4, '0', STR_PAD_LEFT) }}</span>
                                </div>
                                <p class="font-semibold text-slate-800">{{ $it->title }}</p>
                                <p class="mt-1 text-xs text-slate-500">{{ $it->category }} • {{ $it->created_at->diffForHumans() }} @if($it->due_category && $it->due_category !== 'none') • Due: {{ $it->due_at ? $it->due_at->diffForHumans() : '—' }} @endif</p>
                            </div>
                            <div class="flex items-center gap-2">
                                <a href="{{ route('incidents.show', ['incident' => $it->id]) }}" class="rounded-lg bg-indigo-600 px-4 py-2 text-xs font-bold text-white">Open</a>
                            </div>
                        </div>
                    @endforeach
                </div>
            </section>
        @endif

        <section class="grid gap-4 xl:grid-cols-4">
            <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">Total Incidents</p>
                <p class="mt-2 text-5xl font-bold text-slate-800">{{ $summary->total }}</p>
                <p class="mt-2 text-xs font-semibold text-indigo-600">+2% from avg</p>
            </article>
            <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">Open</p>
                <p class="mt-2 text-5xl font-bold text-slate-800">{{ $incidents->where('status', 'open')->count() }}</p>
                <p class="mt-2 text-xs font-semibold text-orange-600">{{ $summary->investigating }} in progress</p>
            </article>
            <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">Critical</p>
                <p class="mt-2 text-5xl font-bold text-rose-600">{{ $summary->critical_open }}</p>
                <p class="mt-2 text-xs font-semibold text-rose-600">Action Required</p>
            </article>
            <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">Resolved Today</p>
                <p id="resolvedTodayCount" class="mt-2 text-5xl font-bold text-indigo-700">{{ $summary->resolved_today }}</p>
            </article>
        </section>

        <section class="grid gap-4 xl:grid-cols-4">
            <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm xl:col-span-3">
                <div class="mb-4 flex items-center justify-between">
                    <div>
                        <h3 class="text-3xl font-bold text-slate-800">Incident Trend</h3>
                    </div>
                    <div class="rounded-lg bg-indigo-50 p-1 text-xs font-semibold text-indigo-700">
                        <button id="trend-daily-btn" data-mode="daily" class="trend-tab rounded-md bg-indigo-100 px-2 py-1">Daily</button>
                        <button id="trend-weekly-btn" data-mode="weekly" class="trend-tab px-2 py-1 text-slate-400">Weekly</button>
                    </div>
                </div>

                @php
                    // aim: reduce empty space under chart by lowering bottom padding and extending area fill
                    $maxTrend = max(1, $trendData->max('value'));
                @endphp
                @php
                    $points = $trendData->values()->map(function ($point, $index) use ($trendData, $maxTrend) {
                        $maxIndex = max(1, $trendData->count() - 1);
                        $x = ($index / $maxIndex) * 100;
                        // moderate amplitude and offset so points sit comfortably above labels but area reaches lower
                        $y = 100 - (($point['value'] / $maxTrend) * 58 + 12);

                        return number_format($x, 2, '.', '').','.number_format($y, 2, '.', '');
                    })->implode(' ');
                    // close area nearer to bottom so fill reduces empty space
                    $areaPoints = '0,96 '.$points.' 100,96';
                @endphp

                    <div id="trend-container" class="h-72 rounded-xl border border-indigo-100 bg-gradient-to-b from-indigo-50/70 to-transparent p-4 pb-4">
                    <svg id="trend-svg" class="h-full w-full" viewBox="0 0 100 100" preserveAspectRatio="none" role="img" aria-label="Incident trend chart">
                        <defs>
                            <linearGradient id="areaFill" x1="0" y1="0" x2="0" y2="1">
                                <stop offset="0%" stop-color="#6366f1" stop-opacity="0.35" />
                                <stop offset="100%" stop-color="#6366f1" stop-opacity="0.04" />
                            </linearGradient>
                        </defs>
                        <polyline points="0,68 100,68" fill="none" stroke="#e2e8f0" stroke-width="0.9" />
                        <polyline points="0,42 100,42" fill="none" stroke="#e2e8f0" stroke-width="0.9" />
                        <polygon id="trend-area" points="{{ $areaPoints }}" fill="url(#areaFill)" />
                        <polyline id="trend-line" points="{{ $points }}" fill="none" stroke="#4f46e5" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                    </svg>

                    <div id="trend-labels" class="mt-8 grid grid-cols-7 gap-1">
                        @foreach ($trendData as $point)
                            <span class="text-center text-[12px] font-semibold uppercase text-slate-500 leading-5">{{ $point['label'] }}</span>
                        @endforeach
                    </div>

                    <script>
                        (function(){
                            const DAILY = @json($dailyTrend ?? $trendData);
                            const WEEKLY = @json($weeklyTrend ?? []);

                            const container = document.getElementById('trend-container');
                            const svg = document.getElementById('trend-svg');
                            const areaEl = document.getElementById('trend-area');
                            const lineEl = document.getElementById('trend-line');
                            const labelsEl = document.getElementById('trend-labels');
                            const dailyBtn = document.getElementById('trend-daily-btn');
                            const weeklyBtn = document.getElementById('trend-weekly-btn');

                            function computePoints(data) {
                                const values = data.map(d => Number(d.value || 0));
                                const max = Math.max(1, ...values);
                                const n = data.length;
                                const points = [];
                                for (let i=0;i<n;i++) {
                                    const x = n === 1 ? 50 : (i / Math.max(1, n - 1)) * 100;
                                    const y = 100 - ((values[i] / max) * 58 + 12);
                                    points.push(x.toFixed(2) + ',' + y.toFixed(2));
                                }
                                const areaCloseY = 96;
                                const area = ['0,'+areaCloseY].concat(points).concat(['100,'+areaCloseY]).join(' ');
                                const line = points.join(' ');
                                return { area, line };
                            }

                            function renderLabels(data) {
                                labelsEl.style.gridTemplateColumns = `repeat(${data.length}, 1fr)`;
                                labelsEl.innerHTML = data.map(d => `<span class="text-center text-[12px] font-semibold uppercase text-slate-500 leading-5">${d.label}</span>`).join('');
                            }

                            function render(data) {
                                if (!data || !data.length) return;
                                const p = computePoints(data);
                                if (areaEl) areaEl.setAttribute('points', p.area);
                                if (lineEl) lineEl.setAttribute('points', p.line);
                                renderLabels(data);
                            }

                            dailyBtn.addEventListener('click', function(){
                                dailyBtn.classList.add('rounded-md','bg-indigo-100'); dailyBtn.classList.remove('text-slate-400');
                                weeklyBtn.classList.remove('rounded-md','bg-indigo-100'); weeklyBtn.classList.add('text-slate-400');
                                render(DAILY);
                            });

                            weeklyBtn.addEventListener('click', function(){
                                weeklyBtn.classList.add('rounded-md','bg-indigo-100'); weeklyBtn.classList.remove('text-slate-400');
                                dailyBtn.classList.remove('rounded-md','bg-indigo-100'); dailyBtn.classList.add('text-slate-400');
                                render(WEEKLY.length ? WEEKLY : DAILY);
                            });

                            // initial state
                            render(DAILY);
                        })();
                    </script>
                </div>
            </article>

            <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <h3 class="text-3xl font-bold text-slate-800">Severity Distribution</h3>
                <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">Current Active States</p>

                @php
                    $criticalCount = $incidents->where('severity', 'critical')->count();
                    $highCount = $incidents->where('severity', 'high')->count();
                    $mediumCount = $incidents->where('severity', 'medium')->count();
                    $lowCount = $incidents->where('severity', 'low')->count();
                @endphp

                @php
                    $totalSeverity = max(1, $criticalCount + $highCount + $mediumCount + $lowCount);
                    $criticalDeg = round(($criticalCount / $totalSeverity) * 360, 2);
                    $highDeg = round(($highCount / $totalSeverity) * 360, 2);
                    $mediumDeg = round(($mediumCount / $totalSeverity) * 360, 2);
                    $firstStop = $criticalDeg;
                    $secondStop = $criticalDeg + $highDeg;
                    $thirdStop = $criticalDeg + $highDeg + $mediumDeg;
                    $ringStyle = "background: conic-gradient(#e11d48 0deg {$firstStop}deg, #d97706 {$firstStop}deg {$secondStop}deg, #64748b {$secondStop}deg {$thirdStop}deg, #6366f1 {$thirdStop}deg 360deg);";
                @endphp

                <div class="mx-auto my-6 grid h-40 w-40 place-items-center rounded-full p-[14px] shadow-inner" style="{{ $ringStyle }}">
                    <div class="grid h-full w-full place-items-center rounded-full bg-white text-center">
                        <p class="text-4xl font-bold text-slate-800">{{ $summary->total }}</p>
                        <p class="-mt-1 text-xs text-slate-400">Total</p>
                    </div>
                </div>

                <ul class="space-y-2 text-sm text-slate-700">
                    <li class="flex items-center justify-between"><span><span class="mr-2 inline-block h-2.5 w-2.5 rounded-full bg-rose-600"></span>Critical</span><strong>{{ $criticalCount }}</strong></li>
                    <li class="flex items-center justify-between"><span><span class="mr-2 inline-block h-2.5 w-2.5 rounded-full bg-amber-600"></span>High</span><strong>{{ $highCount }}</strong></li>
                    <li class="flex items-center justify-between"><span><span class="mr-2 inline-block h-2.5 w-2.5 rounded-full bg-slate-500"></span>Medium</span><strong>{{ $mediumCount }}</strong></li>
                    <li class="flex items-center justify-between"><span><span class="mr-2 inline-block h-2.5 w-2.5 rounded-full bg-indigo-500"></span>Low</span><strong>{{ $lowCount }}</strong></li>
                </ul>
            </article>
        </section>

        @php
            // show complete incidents first, then incomplete ones at the bottom
            $completeIncidents = $incidents->filter(fn($it) => empty($it->incomplete) || $it->incomplete === false)->values();
            $incompleteIncidents = $incidents->filter(fn($it) => ! empty($it->incomplete) && $it->incomplete === true)->values();
            $dashboardIncidents = $completeIncidents->concat($incompleteIncidents)->take(8);
            $dashboardActivities = $recentActivities->take(5);
        @endphp

        <section class="grid items-stretch gap-4 xl:grid-cols-4">
            <article class="flex h-full flex-col xl:col-span-3">
                <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                    <div class="flex flex-wrap items-center gap-2 text-xs font-semibold">
                        <button class="rounded-lg bg-indigo-100 px-3 py-1.5 text-indigo-700">Filters</button>
                        <a href="{{ route('incidents.index') }}" class="rounded-lg border border-slate-200 px-3 py-1.5 text-slate-600">All Severities</a>
                        <a href="{{ route('incidents.index') }}" class="rounded-lg border border-slate-200 px-3 py-1.5 text-slate-600">All Statuses</a>
                        <a href="{{ route('incidents.index') }}" class="rounded-lg border border-slate-200 px-3 py-1.5 text-slate-600">All Categories</a>
                    </div>
                </div>

                <div class="mt-4 flex flex-1 min-h-0 flex-col overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                    <div class="flex-1 min-h-0 overflow-hidden">
                        <table class="min-w-full divide-y divide-slate-200">
                            <thead class="bg-slate-50 text-[11px] font-bold uppercase tracking-wide text-slate-500">
                                <tr>
                                    <th class="px-5 py-2 text-left">Incident</th>
                                    <th class="px-5 py-2 text-left">Severity</th>
                                    <th class="px-5 py-2 text-left">Status</th>
                                    <th class="px-5 py-2 text-left">Assignee</th>
                                    <th class="px-5 py-2 text-left">Action</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 text-sm">
                                @forelse ($dashboardIncidents as $incident)
                                    <tr class="hover:bg-slate-50/70">
                                        <td class="px-5 py-5">
                                            <a href="{{ route('incidents.show', ['incident' => $incident->id]) }}" class="font-semibold text-slate-800 hover:text-indigo-700">{{ $incident->title }}</a>
                                            <p class="mt-1 text-xs text-slate-500">{{ $incident->category }} • {{ $incident->created_at->diffForHumans() }}</p>
                                        </td>
                                        <td class="px-5 py-5">
                                            <span class="rounded-full px-2.5 py-1 text-xs font-bold {{ $incident->badge_color }}">{{ $incident->badge }}</span>
                                        </td>
                                        <td class="px-5 py-5 text-slate-700">
                                            <span class="mr-1 inline-block h-2 w-2 rounded-full {{ $incident->status === 'resolved' ? 'bg-emerald-500' : ($incident->status === 'investigating' ? 'bg-orange-500' : 'bg-rose-500') }}"></span>
                                            {{ $incident->status_label }}
                                        </td>
                                        <td class="px-5 py-5 text-slate-700">
                                            <div class="font-medium text-slate-800">{{ $incident->assignee_name ?? 'Unassigned' }}</div>
                                        </td>
                                        <td class="px-5 py-5">
                                            <a href="{{ route('incidents.edit', ['incident' => $incident->id]) }}" class="text-indigo-700 hover:text-indigo-800">Edit</a>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="px-5 py-8 text-center text-sm text-slate-500">No incidents available.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    <div class="mt-auto px-5 pb-4">
                        <a href="{{ route('incidents.index') }}" class="mt-4 block w-full rounded-lg border border-slate-200 px-4 py-2 text-center text-sm font-semibold text-slate-600 hover:bg-slate-50 hover:text-slate-800">View All Incidents</a>
                    </div>
                </div>
            </article>

            <article class="flex h-full flex-col rounded-2xl border border-slate-200 bg-white p-5 shadow-sm xl:col-span-1">
                <h3 class="text-3xl font-bold text-slate-800">Recent Activity</h3>
                <div class="mt-4 flex-1 space-y-3">
                    @forelse ($dashboardActivities as $activity)
                        <div class="rounded-xl bg-slate-50 p-3">
                            <p class="text-sm font-semibold text-slate-800">{{ $activity->description }}</p>
                            <p class="mt-1 text-xs text-slate-500">{{ $activity->created_at->diffForHumans() }}</p>
                        </div>
                    @empty
                        <p class="text-sm text-slate-500">No recent activity.</p>
                    @endforelse
                </div>
                <a href="{{ route('history.index') }}" class="mt-4 block rounded-lg border border-slate-200 px-4 py-2 text-center text-sm font-semibold text-slate-600 hover:bg-slate-50">View History</a>
            </article>
        </section>
    </div>
    <script>
        (function () {
            const el = document.getElementById('resolvedTodayCount');
            if (!el) return;

            async function fetchSummary() {
                try {
                    const res = await fetch('{{ route('api.dashboard.summary') }}', { credentials: 'same-origin' });
                    if (!res.ok) return;
                    const json = await res.json();
                    el.textContent = json.resolved_today ?? el.textContent;
                } catch (e) {
                    // ignore errors silently
                }
            }

            // initial poll after load
            setTimeout(fetchSummary, 1000);
            // then every 5 seconds
            setInterval(fetchSummary, 5000);
        })();
    </script>
</x-app-layout>
