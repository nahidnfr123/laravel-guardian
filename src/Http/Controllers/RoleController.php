<?php

namespace NahidFerdous\Guardian\Http\Controllers;

use NahidFerdous\Guardian\Models\Role;
use NahidFerdous\Guardian\Support\GuardianCache;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class RoleController extends Controller
{
    public function index()
    {
        return Role::all();
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string',
            'slug' => 'required|string',
        ]);

        $existing = Role::where('slug', $data['slug'])->first();

        if (! $existing) {
            return Role::create($data);
        }

        return response(['error' => 1, 'message' => 'role already exists'], 409);
    }

    public function show(Role $role)
    {
        return $role;
    }

    public function update(Request $request, ?Role $role = null)
    {
        if (! $role) {
            return response(['error' => 1, 'message' => 'role doesn\'t exist'], 404);
        }

        $role->name = $request->name ?? $role->name;

        if ($request->slug) {
            $protected = config('tyro.protected_role_slugs', ['admin', 'super-admin']);
            if (! in_array($role->slug, $protected, true)) {
                $role->slug = $request->slug;
            }
        }

        $dirty = $role->isDirty('slug');
        $role->save();

        if ($dirty) {
            GuardianCache::forgetUsersByRole($role);
        }

        return $role;
    }

    public function destroy(Role $role)
    {
        $protected = config('tyro.protected_role_slugs', ['admin', 'super-admin']);
        if (in_array($role->slug, $protected, true)) {
            return response(['error' => 1, 'message' => 'you cannot delete this role'], 422);
        }

        GuardianCache::forgetUsersByRole($role);
        $role->delete();

        return response(['error' => 0, 'message' => 'role has been deleted']);
    }
}
