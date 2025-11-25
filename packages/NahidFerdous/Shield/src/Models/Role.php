<?php

namespace NahidFerdous\Shield\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Role extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'slug'];

    protected $hidden = ['pivot', 'created_at', 'updated_at'];

    protected $table = 'roles';

    public function users()
    {
        $userClass = config('shield.models.user', config('auth.providers.users.model', 'App\\Models\\User'));

        return $this->belongsToMany($userClass, config('shield.tables.pivot', 'user_roles'));
    }

    public function privileges(): BelongsToMany
    {
        return $this->belongsToMany(
            Privilege::class,
            config('shield.tables.role_privilege', 'privilege_role')
        )->using(RolePrivilege::class)->withTimestamps();
    }

    /**
     * Check if the role has a specific privilege by slug.
     */
    public function hasPrivilege(string $privilegeSlug): bool
    {
        return $this->privileges()->where('slug', $privilegeSlug)->exists();
    }

    /**
     * Check if the role has all of the specified privileges by slug.
     */
    public function hasPrivileges(array $privilegeSlugs): bool
    {
        $rolePrivilegeSlugs = $this->privileges()->pluck('slug')->toArray();

        foreach ($privilegeSlugs as $slug) {
            if (! in_array($slug, $rolePrivilegeSlugs)) {
                return false;
            }
        }

        return true;
    }
}
