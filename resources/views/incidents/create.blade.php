<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="text-xl font-semibold text-slate-900">Create Incident</h2>
            <a href="{{ route('incidents.index') }}" class="text-sm font-medium text-sky-700 hover:text-sky-800">Back to incidents</a>
        </div>
    </x-slot>

    <div class="mx-auto max-w-5xl px-4 py-10 sm:px-6 lg:px-8">
        <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <form method="POST" action="{{ route('incidents.store') }}">
                @csrf
                @include('incidents._form')
            </form>
        </div>
    </div>
</x-app-layout>
