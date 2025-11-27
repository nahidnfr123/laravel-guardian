<?php

namespace NahidFerdous\Shield\Http\Controllers;

use Illuminate\Auth\Events\Registered;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Exceptions\MissingAbilityException;
use NahidFerdous\Shield\Events\ShieldUserRegisterEvent;
use NahidFerdous\Shield\Http\Requests\ShieldCreateUserRequest;
use NahidFerdous\Shield\Models\Role;
use NahidFerdous\Shield\Support\ShieldCache;
use NahidFerdous\Shield\Traits\ApiResponseTrait;

class UserController extends Controller
{
    use ApiResponseTrait;

    /**
     * Get all users
     */
    public function index(): \Illuminate\Http\JsonResponse
    {
        return $this->success('User list', $this->userQuery()->get());
    }

    /**
     * Create a new user
     */
    public function store(ShieldCreateUserRequest $request)
    {
        $userClass = $this->userClass();

        // Check if a user exists
        $existing = $userClass::query()->where('email', $request->email)->first();
        if ($existing) {
            return response(['error' => 1, 'message' => 'User already exists'], 409);
        }

        // Get only fillable fields from the request
        $model = new $userClass;

        // Use validated() if it's NOT the default CreateUserRequest
        if (get_class($request) !== ShieldCreateUserRequest::class) {
            $validatedData = $request->validated();
            $userData = array_intersect_key($validatedData, array_flip($model->getFillable()));
        } else {
            $userData = $request->only($model->getFillable());
        }

        // Hash password if present
        if (isset($userData['password'])) {
            $userData['password'] = Hash::make($userData['password']);
        }

        // Set email_verified_at to null if email verification is enabled
        if (config('shield.auth.check_verified', false)) {
            $userData['email_verified_at'] = null;
        }

        $user = $userClass::create($userData);

        $defaultRoleSlug = config('shield.default_user_role_slug', 'user');
        $user->roles()->attach(Role::where('slug', $defaultRoleSlug)->first());
        ShieldCache::forgetUser($user);

        // Fire the UserRegistered event so, package user can listen to the event and perform other actions
        // Fire the UserRegistered event (handles email verification)
        event(new ShieldUserRegisterEvent($user, $request->only(['name', 'email', 'ip' => $request->ip()])));

        // laravel default email verification can be sent using this event
        // (new Registered($user));

        return $this->success('User created successfully', $user);
    }

    /**
     * Show a specific user
     */
    public function show($user): \Illuminate\Http\JsonResponse
    {
        return $this->success('User details', $this->resolveUser($user));
    }

    /**
     * Update a user
     */
    public function update(Request $request, $user): \Illuminate\Http\JsonResponse
    {
        $user = $this->resolveUser($user);

        $user->name = $request->name ?? $user->name;
        $user->email = $request->email ?? $user->email;
        $user->password = $request->password ? Hash::make($request->password) : $user->password;
        $user->email_verified_at = $request->email_verified_at ?? $user->email_verified_at;

        $loggedInUser = $request->user();
        if ($loggedInUser->id === $user->id) {
            $user->save();

            return $this->success('User updated successfully.', $user);
        }

        if ($loggedInUser->tokenCan('admin') || $loggedInUser->tokenCan('super-admin')) {
            $user->save();

            return $this->success('User updated successfully.', $user);
        }

        throw new MissingAbilityException('Not Authorized');
    }

    /**
     * Delete a user
     */
    public function destroy($user): \Illuminate\Http\JsonResponse
    {
        $user = $this->resolveUser($user);
        $adminRole = Role::where('slug', 'admin')->first();

        if ($adminRole && $user->roles->contains($adminRole)) {
            $adminCount = $adminRole->users()->count();
            if ($adminCount === 1) {
                return $this->failure('Create another admin before deleting this only admin user', 409);
            }
        }

        $user->delete();
        ShieldCache::forgetUser($user);

        return $this->success('User deleted successfully.');
    }

    /**
     * Get the configured user class
     */
    protected function userClass(): string
    {
        return config('shield.models.user', config('auth.providers.users.model', 'App\\Models\\User'));
    }

    /**
     * Get a query builder for the user model
     */
    protected function userQuery()
    {
        $class = $this->userClass();

        return $class::query();
    }

    /**
     * Resolve user from ID or instance
     */
    protected function resolveUser($user): \Illuminate\Contracts\Auth\Authenticatable
    {
        if ($user instanceof \Illuminate\Contracts\Auth\Authenticatable) {
            return $user;
        }

        return $this->userQuery()->findOrFail($user);
    }
}
