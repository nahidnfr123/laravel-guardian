# Auth Requirements

1. Login (sanctum, jwt, passport)
2. Logout
3. Register 🥶
4. Add register even so, package user can listen to event and perform other actions
5. Verify Email
6. Request Email Verification Link
7. Forget Password
8. Password Reset
9. Me 🥶
10. Change Password 🥶
11. Scopes | Ability etc

# Role & Permission Requirements

1. Create Role
2. Create Permission
3. Assign Role to User

## How to Use User register event:

### Create Custom Listeners in User's App

Users can create their own listeners:

```bash
php artisan make:listener LogUserRegistration --event=UserRegistered
```

Then register in their `EventServiceProvider`:

```php
protected $listen = [
    \NahidFerdous\Shield\Events\UserRegistered::class => [
        \App\Listeners\LogUserRegistration::class,
        \App\Listeners\SendSlackNotification::class,
    ],
];
```

### Access Event Data in Listeners:

```php
public function handle(UserRegistered $event): void
{
    $user = $event->user;
    $request = $event->request;
    
    // Your logic here
}
```

This gives users of your package full flexibility to hook into the registration process!
