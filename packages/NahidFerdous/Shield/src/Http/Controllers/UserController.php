<?php

namespace NahidFerdous\Shield\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Exceptions\MissingAbilityException;
use NahidFerdous\Shield\Http\Requests\ShieldCreateUserRequest;
use NahidFerdous\Shield\Http\Requests\ShieldLoginRequest;
use NahidFerdous\Shield\Models\Role;
use NahidFerdous\Shield\Support\ShieldCache;

class UserController extends Controller
{
    public function index()
    {
        return $this->userQuery()->get();
    }

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

        $user = $userClass::create($userData);

        $defaultRoleSlug = config('shield.default_user_role_slug', 'user');
        $user->roles()->attach(Role::where('slug', $defaultRoleSlug)->first());
        ShieldCache::forgetUser($user);

        return $user;
    }

    //    public function store(Request $request)
    //    {
    //        $creds = $request->validate([
    //            'email' => 'required|email',
    //            'password' => 'required',
    //            'name' => 'nullable|string',
    //        ]);
    //
    //        $userClass = $this->userClass();
    //        $existing = $userClass::query()->where('email', $creds['email'])->first();
    //
    //        if ($existing) {
    //            return response(['error' => 1, 'message' => 'user already exists'], 409);
    //        }
    //
    //        /** @var \Illuminate\Database\Eloquent\Model $user */
    //        $user = $userClass::create([
    //            'email' => $creds['email'],
    //            'password' => Hash::make($creds['password']),
    //            'name' => $creds['name'],
    //        ]);
    //
    //        $defaultRoleSlug = config('shield.default_user_role_slug', 'user');
    //        $user->roles()->attach(Role::where('slug', $defaultRoleSlug)->first());
    //        ShieldCache::forgetUser($user);
    //
    //        return $user;
    //    }

    public function login(ShieldLoginRequest $request)
    {
        $data = $request->validated();
        $userClass = $this->userClass();

        // Get the credential field from config
        $credentialField = config('shield.validation.login.credential_field', 'email');

        // Handle multiple credential fields (e.g., 'email|mobile')
        if (str_contains($credentialField, '|')) {
            $fields = explode('|', $credentialField);
            $user = null;

            // Try each field until we find a match
            foreach ($fields as $field) {
                if (isset($data[$field])) {
                    $user = $userClass::where($field, $data[$field])->first();
                    if ($user) {
                        break;
                    }
                }
            }

            // If still no user found, try a generic 'login' field
            if (! $user && isset($data['login'])) {
                foreach ($fields as $field) {
                    $user = $userClass::where($field, $data['login'])->first();
                    if ($user) {
                        break;
                    }
                }
            }
        } else {
            // Single credential field
            $loginValue = $data[$credentialField] ?? $data['login'] ?? null;

            if (! $loginValue) {
                return response(['error' => 1, 'message' => 'invalid credentials'], 401);
            }

            $user = $userClass::where($credentialField, $loginValue)->first();
        }

        // Check credentials
        if (! $user || ! Hash::check($request->password, $user->password)) {
            return response(['error' => 1, 'message' => 'invalid credentials'], 401);
        }

        // Check if user is suspended
        if ($this->userIsSuspended($user)) {
            return response(['error' => 1, 'message' => 'user is suspended'], 423);
        }

        // Check if verification is required
        if (config('shield.validation.login.check_verified', false)) {
            $verificationField = config('shield.validation.login.verification_field', 'email_verified_at');

            if (! $user->{$verificationField}) {
                return response(['error' => 1, 'message' => 'account not verified'], 403);
            }
        }

        // Delete previous tokens if configured
        if (config('shield.delete_previous_access_tokens_on_login', false)) {
            $user->tokens()->delete();
        }

        // Create token with roles
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
