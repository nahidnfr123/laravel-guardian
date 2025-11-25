<?php

namespace NahidFerdous\Shield\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Exceptions\MissingAbilityException;
use NahidFerdous\Shield\Models\Role;
use NahidFerdous\Shield\Support\ShieldCache;

class UserController extends Controller
{
    public function index()
    {
        return $this->userQuery()->get();
    }

    public function store(Request $request)
    {
        $creds = $request->validate([
            'email' => 'required|email',
            'password' => 'required',
            'name' => 'nullable|string',
        ]);

        $userClass = $this->userClass();
        $existing = $userClass::query()->where('email', $creds['email'])->first();

        if ($existing) {
            return response(['error' => 1, 'message' => 'user already exists'], 409);
        }

        /** @var \Illuminate\Database\Eloquent\Model $user */
        $user = $userClass::create([
            'email' => $creds['email'],
            'password' => Hash::make($creds['password']),
            'name' => $creds['name'],
        ]);

        $defaultRoleSlug = config('shield.default_user_role_slug', 'user');
        $user->roles()->attach(Role::where('slug', $defaultRoleSlug)->first());
        ShieldCache::forgetUser($user);

        return $user;
    }

    public function login(Request $request)
    {
        $creds = $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        $userClass = $this->userClass();
        $user = $userClass::where('email', $creds['email'])->first();

        if (! $user || ! Hash::check($request->password, $user->password)) {
            return response(['error' => 1, 'message' => 'invalid credentials'], 401);
        }

        if ($this->userIsSuspended($user)) {
            return response(['error' => 1, 'message' => 'user is suspended'], 423);
        }

        if (config('shield.delete_previous_access_tokens_on_login', false)) {
            $user->tokens()->delete();
        }

        $roles = $user->roles->pluck('slug')->all();
        $token = $user->createToken('shield-api-token', $roles)->plainTextToken;

        return response(['error' => 0, 'id' => $user->id, 'name' => $user->name, 'token' => $token], 200);
    }

    public function show($user)
    {
        return $this->resolveUser($user);
    }

    public function update(Request $request, $user)
    {
        $user = $this->resolveUser($user);

        $user->name = $request->name ?? $user->name;
        $user->email = $request->email ?? $user->email;
        $user->password = $request->password ? Hash::make($request->password) : $user->password;
        $user->email_verified_at = $request->email_verified_at ?? $user->email_verified_at;

        $loggedInUser = $request->user();
        if ($loggedInUser->id === $user->id) {
            $user->save();

            return $user;
        }

        if ($loggedInUser->tokenCan('admin') || $loggedInUser->tokenCan('super-admin')) {
            $user->save();

            return $user;
        }

        throw new MissingAbilityException('Not Authorized');
    }

    public function destroy($user)
    {
        $user = $this->resolveUser($user);
        $adminRole = Role::where('slug', 'admin')->first();

        if ($adminRole && $user->roles->contains($adminRole)) {
            $adminCount = $adminRole->users()->count();
            if ($adminCount === 1) {
                return response(['error' => 1, 'message' => 'Create another admin before deleting this only admin user'], 409);
            }
        }

        $user->delete();
        ShieldCache::forgetUser($user);

        return response(['error' => 0, 'message' => 'user deleted']);
    }

    public function me(Request $request)
    {
        return $request->user();
    }

    protected function userClass(): string
    {
        return config('shield.models.user', config('auth.providers.users.model', 'App\\Models\\User'));
    }

    protected function userQuery()
    {
        $class = $this->userClass();

        return $class::query();
    }

    protected function resolveUser($user)
    {
        if ($user instanceof \Illuminate\Contracts\Auth\Authenticatable) {
            return $user;
        }

        return $this->userQuery()->findOrFail($user);
    }

    protected function userIsSuspended($user): bool
    {
        if (method_exists($user, 'isSuspended')) {
            return $user->isSuspended();
        }

        return (bool) ($user->suspended_at ?? false);
    }
}
