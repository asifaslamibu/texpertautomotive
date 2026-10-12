<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    public function index()
    {
        $users = User::with('role')
            ->where('is_admin', true)
            ->orderBy('name')
            ->paginate(20);

        return view('admin.users.index', compact('users'));
    }

    public function create()
    {
        return view('admin.users.form', [
            'user' => new User(),
            'roles' => Role::orderByDesc('is_super')->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:160', Rule::unique('users', 'email')],
            'role_id' => ['required', Rule::exists('roles', 'id')],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'role_id' => $data['role_id'],
            'password' => $data['password'],
            'is_admin' => true,
        ]);

        return redirect()->route('admin.users.index')->with('status', 'Staff member created.');
    }

    public function edit(User $user)
    {
        abort_unless($user->is_admin, 404);

        return view('admin.users.form', [
            'user' => $user,
            'roles' => Role::orderByDesc('is_super')->orderBy('name')->get(),
        ]);
    }

    public function update(Request $request, User $user)
    {
        abort_unless($user->is_admin, 404);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:160', Rule::unique('users', 'email')->ignore($user->id)],
            'role_id' => ['required', Rule::exists('roles', 'id')],
            'password' => ['nullable', 'string', 'min:8', 'confirmed'],
        ]);

        // Guard: don't let the last super admin demote themselves out of the role.
        if ($user->isSuperAdmin() && (int) $data['role_id'] !== $user->role_id && $this->superAdminCount() <= 1) {
            return back()->withErrors(['role_id' => 'You cannot change the role of the only Administrator.'])->withInput();
        }

        $user->fill([
            'name' => $data['name'],
            'email' => $data['email'],
            'role_id' => $data['role_id'],
        ]);

        if (! empty($data['password'])) {
            $user->password = $data['password'];
        }

        $user->save();

        return redirect()->route('admin.users.index')->with('status', 'Staff member updated.');
    }

    public function destroy(User $user)
    {
        abort_unless($user->is_admin, 404);

        if ($user->id === Auth::id()) {
            return back()->withErrors(['email' => 'You cannot delete your own account.']);
        }

        if ($user->isSuperAdmin() && $this->superAdminCount() <= 1) {
            return back()->withErrors(['email' => 'You cannot delete the only Administrator.']);
        }

        $user->delete();

        return back()->with('status', 'Staff member removed.');
    }

    private function superAdminCount(): int
    {
        return User::whereHas('role', fn ($q) => $q->where('is_super', true))->count();
    }
}
