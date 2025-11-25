<?php

namespace NahidFerdous\Guardian\Models;

use NahidFerdous\Guardian\Support\GuardianCache;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\Pivot;

class RolePrivilege extends Pivot
{
    use HasFactory;

    protected $table = 'privilege_role';

    protected $fillable = ['role_id', 'privilege_id'];

    public $timestamps = true;

    protected static function booted(): void
    {
        static::saved(function (self $pivot): void {
            GuardianCache::forgetUsersByRoleIds([$pivot->role_id]);
        });

        static::deleted(function (self $pivot): void {
            GuardianCache::forgetUsersByRoleIds([$pivot->role_id]);
        });
    }

    public function getTable()
    {
        return config('tyro.tables.role_privilege', parent::getTable());
    }
}
