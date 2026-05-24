<x-app-layout>
    <div class="space-y-5">
        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <h1 class="text-3xl font-bold text-slate-800">Edit User</h1>
            <p class="mt-1 text-sm text-slate-500">Update the user account details.</p>
        </div>

        <form method="POST" action="{{ route('admin.users.update', $user) }}" class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            @csrf
            @method('PUT')
            @include('admin.users._form', ['mode' => 'edit'])
        </form>
    </div>
</x-app-layout>