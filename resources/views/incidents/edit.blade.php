<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="text-xl font-semibold text-slate-900">Edit Incident</h2>
            <a href="{{ route('incidents.show', ['incident' => $incident->id]) }}" class="text-sm font-medium text-sky-700 hover:text-sky-800">View detail</a>
        </div>
    </x-slot>

    <div class="mx-auto max-w-5xl px-4 py-10 sm:px-6 lg:px-8">
        <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <form method="POST" action="{{ route('incidents.update', ['incident' => $incident->id]) }}">
                @csrf
                @method('PUT')
                @include('incidents._form', ['incident' => $incident])
            </form>
        </div>
    </div>
</x-app-layout>
