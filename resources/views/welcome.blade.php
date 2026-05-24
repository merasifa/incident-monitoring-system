<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>{{ config('app.name', 'Operational Attention Monitoring System') }}</title>
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="min-h-screen bg-slate-50 font-sans text-slate-900">
        <div class="mx-auto flex min-h-screen max-w-6xl flex-col px-6 py-8">
            <header class="flex items-center justify-between">
                <h1 class="rounded-lg bg-sky-100 px-3 py-1.5 text-xs font-bold tracking-wider text-sky-700">OAMS MVP</h1>
                <div class="flex items-center gap-3 text-sm font-medium">
                    @auth
                        <a href="{{ route('dashboard') }}" class="rounded-xl border border-slate-300 px-4 py-2 hover:bg-slate-100">Dashboard</a>
                    @else
                        <a href="{{ route('login') }}" class="rounded-xl border border-slate-300 px-4 py-2 hover:bg-slate-100">Login</a>
                        <a href="{{ route('register') }}" class="rounded-xl bg-sky-700 px-4 py-2 text-white hover:bg-sky-800">Register</a>
                    @endauth
                </div>
            </header>

            <main class="my-auto grid gap-10 py-10 lg:grid-cols-2 lg:items-center">
                <section>
                    <p class="mb-3 text-sm font-semibold uppercase tracking-wide text-sky-700">Operational Attention Monitoring System</p>
                    <h2 class="text-4xl font-extrabold leading-tight text-slate-900">Detect critical incidents early. Prioritize what matters first.</h2>
                    <p class="mt-4 text-slate-600">An enterprise-style incident dashboard focused on anomaly visibility, urgency prioritization, and fast operational decision-making.</p>
                    <div class="mt-8 flex flex-wrap gap-3">
                        <a href="{{ route('dashboard') }}" class="rounded-xl bg-slate-900 px-5 py-3 text-sm font-semibold text-white hover:bg-slate-800">Open Monitoring Dashboard</a>
                        <a href="{{ route('incidents.index') }}" class="rounded-xl border border-slate-300 px-5 py-3 text-sm font-semibold text-slate-700 hover:bg-white">View Incident Queue</a>
                    </div>
                </section>

                <section class="space-y-4 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                    <h3 class="text-lg font-semibold text-slate-900">MVP Focus</h3>
                    <ul class="space-y-3 text-sm text-slate-600">
                        <li class="rounded-xl bg-rose-50 p-3"><span class="font-semibold text-rose-700">Critical Alerting</span> with pinned top incident and urgency badges.</li>
                        <li class="rounded-xl bg-orange-50 p-3"><span class="font-semibold text-orange-700">Severity Prioritization</span> for faster triage and reduced noise.</li>
                        <li class="rounded-xl bg-sky-50 p-3"><span class="font-semibold text-sky-700">Operational Workflow</span> from Open to Investigating to Resolved.</li>
                        <li class="rounded-xl bg-emerald-50 p-3"><span class="font-semibold text-emerald-700">Monitoring Clarity</span> with summary, trend, filter, and recent activity.</li>
                    </ul>
                </section>
            </main>
        </div>
    </body>
</html>
