<x-app-layout>
    <div class="space-y-5">
        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <h1 class="text-3xl font-bold text-slate-800">Add User</h1>
            <p class="mt-1 text-sm text-slate-500">Create an admin or operator account.</p>
        </div>

        <form method="POST" action="{{ route('admin.users.store') }}" class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            @csrf
            @include('admin.users._form', ['mode' => 'create'])
        </form>
    </div>
</x-app-layout>