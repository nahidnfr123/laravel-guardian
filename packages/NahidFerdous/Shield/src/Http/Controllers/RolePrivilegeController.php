<?php

namespace NahidFerdous\Shield\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Validation\Rule;
use NahidFerdous\Shield\Models\Privilege;
use NahidFerdous\Shield\Models\Role;
use NahidFerdous\Shield\Support\ShieldCache;
use NahidFerdous\Shield\Traits\ApiResponseTrait;

class RolePrivilegeController extends Controller
{
    use ApiResponseTrait;

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
                Rule::exists(config('shield.tables.privileges', 'privileges'), 'id'),
            ],
        ]);

        $privilege = Privilege::findOrFail($data['privilege_id']);
        $role->privileges()->syncWithoutDetaching($privilege);
        ShieldCache::forgetUsersByRole($role);

        return $role->load('privileges');
    }

    public function destroy(Role $role, Privilege $privilege)
    {
        $role->privileges()->detach($privilege);
        ShieldCache::forgetUsersByRole($role);

        return $role->load('privileges');
    }
}
