<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class UserManagementController extends Controller
{
    public function index(Request $request): View
    {
        $query = User::query();

        $query->when($request->filled('q'), function ($builder) use ($request) {
            $term = trim((string) $request->query('q'));
            $like = '%' . str_replace(' ', '%', $term) . '%';

            $builder->where(function ($inner) use ($like) {
                $inner->where('name', 'like', $like)
                    ->orWhere('email', 'like', $like);
            });
        });

        $query->when($request->filled('role'), fn($builder) => $builder->where('role', $request->string('role')->toString()));

        $query->when($request->filled('status'), function ($builder) use ($request) {
            match ($request->string('status')->toString()) {
                'verified' => $builder->whereNotNull('email_verified_at'),
                'unverified' => $builder->whereNull('email_verified_at'),
                default => null,
            };
        });

        $perPage = (int) $request->integer('per_page', 12);
        $perPage = in_array($perPage, [10, 12, 25, 50], true) ? $perPage : 12;

        $users = $query
            ->orderByRaw("CASE WHEN role = 'admin' THEN 0 ELSE 1 END")
            ->orderBy('name')
            ->paginate($perPage)
            ->withQueryString();

        $totals = [
            'all' => User::count(),
            'admin' => User::where('role', 'admin')->count(),
            'operator' => User::where('role', 'operator')->count(),
            'verified' => User::whereNotNull('email_verified_at')->count(),
            'unverified' => User::whereNull('email_verified_at')->count(),
        ];

        return view('admin.users.index', compact('users', 'totals'));
    }

    public function create(): View
    {
        return view('admin.users.create', [
            'user' => new User(['role' => 'operator', 'is_admin' => false]),
            'mode' => 'create',
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'role' => ['required', Rule::in(['admin', 'operator'])],
        ]);

        $isAdmin = $data['role'] === 'admin';

        User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'role' => $data['role'],
            'is_admin' => $isAdmin,
        ]);

        return redirect()->route('admin.users.index')->with('success', 'User created successfully.');
    }

    public function edit(User $user): View
    {
        return view('admin.users.edit', [
            'user' => $user,
            'mode' => 'edit',
        ]);
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'password' => ['nullable', 'string', 'min:8', 'confirmed'],
            'role' => ['required', Rule::in(['admin', 'operator'])],
        ]);

        if ((int) $request->user()->id === (int) $user->id && $data['role'] !== 'admin') {
            return back()->with('error', 'You cannot demote your own admin account.');
        }

        if ($user->role === 'admin' && $data['role'] !== 'admin') {
            $adminCount = User::query()->where('role', 'admin')->count();
            if ($adminCount <= 1) {
                return back()->with('error', 'At least one admin account must remain.');
            }
        }

        $user->name = $data['name'];
        $user->email = $data['email'];
        $user->role = $data['role'];
        $user->is_admin = $data['role'] === 'admin';

        if (! empty($data['password'])) {
            $user->password = Hash::make($data['password']);
        }

        $user->save();

        return redirect()->route('admin.users.index')->with('success', 'User updated successfully.');
    }

    public function destroy(Request $request, User $user): RedirectResponse
    {
        if ((int) $request->user()->id === (int) $user->id) {
            return back()->with('error', 'You cannot delete your own account from user management.');
        }

        if ($user->role === 'admin') {
            $adminCount = User::query()->where('role', 'admin')->count();
            if ($adminCount <= 1) {
                return back()->with('error', 'At least one admin account must remain.');
            }
        }

        $user->delete();

        return redirect()->route('admin.users.index')->with('success', 'User deleted successfully.');
    }

    public function bulkAction(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'action' => ['required', Rule::in(['delete', 'make_admin', 'make_operator'])],
            'user_ids' => ['required', 'array', 'min:1'],
            'user_ids.*' => ['integer', 'exists:users,id'],
        ]);

        $selectedIds = collect($data['user_ids'])->map(fn($id) => (int) $id)->unique()->values();
        $currentUserId = (int) $request->user()->id;
        $selectedUsers = User::query()->whereIn('id', $selectedIds)->get();

        $skipped = [];

        if ($data['action'] === 'delete') {
            $adminCount = User::query()->where('role', 'admin')->count();

            foreach ($selectedUsers as $user) {
                if ((int) $user->id === $currentUserId) {
                    $skipped[] = $user->name . ' (self)';
                    continue;
                }

                if ($user->role === 'admin' && $adminCount <= 1) {
                    $skipped[] = $user->name . ' (last admin)';
                    continue;
                }

                if ($user->role === 'admin') {
                    $adminCount--;
                }

                $user->delete();
            }
        } else {
            $targetRole = $data['action'] === 'make_admin' ? 'admin' : 'operator';
            $adminCount = User::query()->where('role', 'admin')->count();

            foreach ($selectedUsers as $user) {
                if ((int) $user->id === $currentUserId && $targetRole !== 'admin') {
                    $skipped[] = $user->name . ' (self demotion blocked)';
                    continue;
                }

                if ($user->role === 'admin' && $targetRole !== 'admin' && $adminCount <= 1) {
                    $skipped[] = $user->name . ' (last admin)';
                    continue;
                }

                if ($user->role === 'admin' && $targetRole !== 'admin') {
                    $adminCount--;
                }

                $user->role = $targetRole;
                $user->is_admin = $targetRole === 'admin';
                $user->save();
            }
        }

        $message = match ($data['action']) {
            'delete' => 'Bulk delete completed.',
            'make_admin' => 'Selected users promoted to admin.',
            'make_operator' => 'Selected users demoted to operator.',
        };

        if ($skipped !== []) {
            $message .= ' Skipped: ' . implode(', ', $skipped) . '.';
        }

        return redirect()->route('admin.users.index', $request->query())->with('success', $message);
    }
}
