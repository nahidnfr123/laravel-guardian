<?php

namespace NahidFerdous\Shield\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use NahidFerdous\Shield\Models\Role;
use NahidFerdous\Shield\Support\ShieldCache;
use NahidFerdous\Shield\Traits\ApiResponseTrait;

class UserRoleController extends Controller
{
    use ApiResponseTrait;

    public function index($user)
    {
        $user = $this->resolveUser($user);

        return $user->load('roles');
    }

    public function store(Request $request, $user)
    {
        $user = $this->resolveUser($user);
        $data = $request->validate([
            'role_id' => 'required|integer',
        ]);

        $role = Role::findOrFail($data['role_id']);
        if (! $user->roles()->find($role->id)) {
            $user->roles()->attach($role);
            ShieldCache::forgetUser($user);
        }

        return $user->load('roles');
    }

    public function destroy($user, Role $role)
    {
        $user = $this->resolveUser($user);
        $user->roles()->detach($role);
        ShieldCache::forgetUser($user);

        return $user->load('roles');
    }

    protected function resolveUser($user)
    {
        $class = config('shield.models.user', config('auth.providers.users.model', 'App\\Models\\User'));

        if ($user instanceof $class) {
            return $user;
        }

        return $class::query()->findOrFail($user);
    }
}
