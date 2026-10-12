<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class RoleController extends Controller
{
    public function index()
    {
        $roles = Role::withCount('users')->orderByDesc('is_super')->orderBy('name')->get();

        return view('admin.roles.index', compact('roles'));
    }

    public function create()
    {
        return view('admin.roles.form', [
            'role' => new Role(['permissions' => []]),
            'permissions' => Role::PERMISSIONS,
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validateData($request);

        Role::create([
            'name' => $data['name'],
            'slug' => $this->uniqueSlug($data['name']),
            'description' => $data['description'] ?? null,
            'permissions' => $data['permissions'] ?? [],
            'read_only' => $request->boolean('read_only'),
            'is_super' => false,
        ]);

        return redirect()->route('admin.roles.index')->with('status', 'Role created.');
    }

    public function edit(Role $role)
    {
        return view('admin.roles.form', [
            'role' => $role,
            'permissions' => Role::PERMISSIONS,
        ]);
    }

    public function update(Request $request, Role $role)
    {
        // Super (Administrator) role is fixed — it always has full access.
        if ($role->is_super) {
            $data = $request->validate([
                'name' => ['required', 'string', 'max:120'],
                'description' => ['nullable', 'string', 'max:300'],
            ]);
            $role->update(['name' => $data['name'], 'description' => $data['description'] ?? null]);

            return redirect()->route('admin.roles.index')->with('status', 'Role updated.');
        }

        $data = $this->validateData($request);

        $role->update([
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'permissions' => $data['permissions'] ?? [],
            'read_only' => $request->boolean('read_only'),
        ]);

        return redirect()->route('admin.roles.index')->with('status', 'Role updated.');
    }

    public function destroy(Role $role)
    {
        if ($role->is_super) {
            return back()->withErrors(['name' => 'The Administrator role cannot be deleted.']);
        }

        if ($role->users()->exists()) {
            return back()->withErrors(['name' => 'Reassign the staff in this role before deleting it.']);
        }

        $role->delete();

        return back()->with('status', 'Role deleted.');
    }

    private function validateData(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'description' => ['nullable', 'string', 'max:300'],
            'permissions' => ['nullable', 'array'],
            'permissions.*' => [Rule::in(array_keys(Role::PERMISSIONS))],
            'read_only' => ['nullable', 'boolean'],
        ]);
    }

    private function uniqueSlug(string $name): string
    {
        $base = Str::slug($name) ?: 'role';
        $slug = $base;
        $i = 1;
        while (Role::where('slug', $slug)->exists()) {
            $slug = $base.'-'.$i++;
        }

        return $slug;
    }
}
