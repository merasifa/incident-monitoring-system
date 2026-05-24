@php
    $editing = isset($incident);
@endphp

@if($editing)
    <div class="mb-5 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
        Core incident fields are locked for traceability. Only status can be updated after creation.
    </div>
@endif

<div class="grid gap-5 md:grid-cols-2">
    <div class="md:col-span-2">
        <x-input-label for="title" value="Title" />
        @if($editing)
            <div class="mt-1 block w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-slate-700">{{ $incident->title }}</div>
        @else
            <x-text-input id="title" name="title" type="text" class="mt-1 block w-full" :value="old('title', $incident->title ?? '')" required maxlength="120" />
            <x-input-error class="mt-2" :messages="$errors->get('title')" />
        @endif
    </div>

    <div class="md:col-span-2">
        <x-input-label for="description" value="Description" />
        @if($editing)
            <div class="mt-1 block w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-slate-700">{{ $incident->description }}</div>
        @else
            <textarea id="description" name="description" rows="4" class="mt-1 block w-full rounded-xl border-slate-300 focus:border-sky-500 focus:ring-sky-500">{{ old('description', $incident->description ?? '') }}</textarea>
            <x-input-error class="mt-2" :messages="$errors->get('description')" />
        @endif
    </div>

    <div>
        <x-input-label for="category" value="Category" />
        @if($editing)
            <div class="mt-1 block w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-slate-700">{{ $incident->category }}</div>
        @else
            <x-text-input id="category" name="category" type="text" class="mt-1 block w-full" :value="old('category', $incident->category ?? '')" required maxlength="80" />
            <x-input-error class="mt-2" :messages="$errors->get('category')" />
        @endif
    </div>

    <div>
        <x-input-label for="severity" value="Severity" />
        @if($editing)
            <div class="mt-1 block w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-slate-700">{{ ucfirst($incident->severity) }}</div>
        @else
            <select id="severity" name="severity" class="mt-1 block w-full rounded-xl border-slate-300 focus:border-sky-500 focus:ring-sky-500" required>
                @foreach (['critical' => 'Critical', 'high' => 'High', 'medium' => 'Medium', 'low' => 'Low'] as $value => $label)
                    <option value="{{ $value }}" @selected(old('severity', $incident->severity ?? '') === $value)>{{ $label }}</option>
                @endforeach
            </select>
            <x-input-error class="mt-2" :messages="$errors->get('severity')" />
        @endif
    </div>

    <div>
        <x-input-label for="status" value="Status" />
        @php
            // on create, status defaults to open and should not be selectable
            $allStatuses = ['open' => 'Open', 'investigating' => 'Investigating', 'resolved' => 'Resolved'];
            if ($editing) {
                if ($incident->status === 'open') {
                    $allowed = ['investigating', 'resolved', 'open'];
                } elseif ($incident->status === 'investigating') {
                    $allowed = ['investigating', 'resolved'];
                } elseif ($incident->status === 'resolved') {
                    $allowed = ['investigating', 'resolved'];
                } else {
                    $allowed = array_keys($allStatuses);
                }
            } else {
                $allowed = ['open'];
            }
        @endphp

        @if($editing)
            <select id="status" name="status" class="mt-1 block w-full rounded-xl border-slate-300 focus:border-sky-500 focus:ring-sky-500" required>
                @foreach ($allStatuses as $value => $label)
                    @if(in_array($value, $allowed))
                        <option value="{{ $value }}" @selected(old('status', $incident->status ?? 'open') === $value)>{{ $label }}</option>
                    @endif
                @endforeach
            </select>
        @else
            <input type="hidden" name="status" value="open" />
            <div class="mt-1 text-sm text-slate-500">Status: <strong>Open</strong></div>
        @endif
        <x-input-error class="mt-2" :messages="$errors->get('status')" />
    </div>

    <div>
        <x-input-label for="due_at" value="Due Date" />
        @if($editing)
            <div class="mt-1 block w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-slate-700">{{ $incident->due_at ? $incident->due_at->toDayDateTimeString() : '—' }}</div>
        @else
            <x-text-input id="due_at" name="due_at" type="datetime-local" class="mt-1 block w-full" :value="old('due_at', $editing && $incident->due_at ? $incident->due_at->format('Y-m-d\\TH:i') : '')" />
            <x-input-error class="mt-2" :messages="$errors->get('due_at')" />
        @endif
    </div>
</div>

<div class="mt-6 flex items-center justify-end gap-3">
    <a href="{{ route('incidents.index') }}" class="rounded-xl border border-slate-300 px-4 py-2 text-sm font-medium text-slate-600 hover:bg-slate-50">
        Cancel
    </a>
    @if($editing && $incident->status === 'resolved')
        <button disabled class="rounded-xl bg-slate-200 px-4 py-2 text-sm font-medium text-slate-600">Incident Resolved</button>
    @else
        <x-primary-button>
            {{ $editing ? 'Update Incident' : 'Create Incident' }}
        </x-primary-button>
    @endif
</div>
