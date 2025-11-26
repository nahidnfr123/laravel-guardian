<?php

namespace NahidFerdous\Shield\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Validation\Rule;
use NahidFerdous\Shield\Models\Privilege;
use NahidFerdous\Shield\Support\ShieldCache;

class PrivilegeController extends Controller
{
    public function index()
    {
        return Privilege::with('roles:id,name,slug')->get();
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string'],
            'slug' => ['required', 'string', 'max:255', Rule::unique(config('shield.tables.privileges', 'privileges'), 'slug')],
            'description' => ['nullable', 'string'],
        ]);

        $privilege = Privilege::create($data);

        return $privilege->fresh('roles:id,name,slug');
    }

    public function show(Privilege $privilege)
    {
        return $privilege->load('roles:id,name,slug');
    }

    public function update(Request $request, Privilege $privilege)
    {
        $data = $request->validate([
            'name' => ['sometimes', 'string'],
            'slug' => ['sometimes', 'string', 'max:255', Rule::unique(config('shield.tables.privileges', 'privileges'), 'slug')->ignore($privilege->id)],
            'description' => ['nullable', 'string'],
        ]);

        $privilege->fill($data);
        $dirty = $privilege->isDirty('slug');
        $privilege->save();

        if ($dirty) {
            ShieldCache::forgetUsersByPrivilege($privilege);
        }

        return $privilege->fresh('roles:id,name,slug');
    }

    public function destroy(Privilege $privilege)
    {
        ShieldCache::forgetUsersByPrivilege($privilege);
        $privilege->roles()->detach();
        $privilege->delete();

        return response(['error' => 0, 'message' => 'privilege has been deleted']);
    }
}
