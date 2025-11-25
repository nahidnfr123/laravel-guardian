<?php

namespace NahidFerdous\Guardian\Http\Controllers;

use NahidFerdous\Guardian\Models\Privilege;
use NahidFerdous\Guardian\Models\Role;
use NahidFerdous\Guardian\Support\GuardianCache;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Validation\Rule;

class RolePrivilegeController extends Controller
{
    public function index(Role $role)
    {
        return $role->load('privileges');
    }

    public function store(Request $request, Role $role)
    {
        $data = $request->validate([
            'privilege_id' => [
                'required',
                'integer',
                Rule::exists(config('tyro.tables.privileges', 'privileges'), 'id'),
            ],
        ]);

        $privilege = Privilege::findOrFail($data['privilege_id']);
        $role->privileges()->syncWithoutDetaching($privilege);
        GuardianCache::forgetUsersByRole($role);

        return $role->load('privileges');
    }

    public function destroy(Role $role, Privilege $privilege)
    {
        $role->privileges()->detach($privilege);
        GuardianCache::forgetUsersByRole($role);

        return $role->load('privileges');
    }
}
