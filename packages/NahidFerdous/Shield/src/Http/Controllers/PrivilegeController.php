<?php

namespace NahidFerdous\Shield\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Validation\Rule;
use NahidFerdous\Shield\Models\Privilege;
use NahidFerdous\Shield\Support\ShieldCache;
use NahidFerdous\Shield\Traits\ApiResponseTrait;

class PrivilegeController extends Controller
{
    use ApiResponseTrait;

    public function index(): \Illuminate\Http\JsonResponse
    {
        return $this->success('List of privileges', Privilege::with('roles:id,name,slug')->get());
    }

    public function store(Request $request): \Illuminate\Http\JsonResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string'],
            'slug' => ['required', 'string', 'max:255', Rule::unique(config('shield.tables.privileges', 'privileges'), 'slug')],
            'description' => ['nullable', 'string'],
        ]);

        $privilege = Privilege::create($data);

        return $this->success('Privilege created', $privilege->fresh('roles:id,name,slug'));
    }

    public function show(Privilege $privilege): \Illuminate\Http\JsonResponse
    {
        return $this->success('Privilege details', $privilege->load('roles:id,name,slug'));
    }

    public function update(Request $request, Privilege $privilege): \Illuminate\Http\JsonResponse
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

        return $this->success('Privilege updated', $privilege->fresh('roles:id,name,slug'));
    }

    public function destroy(Privilege $privilege): \Illuminate\Http\JsonResponse
    {
        ShieldCache::forgetUsersByPrivilege($privilege);
        $privilege->roles()->detach();
        $privilege->delete();

        return $this->success('Privilege deleted successfully.');
    }
}
