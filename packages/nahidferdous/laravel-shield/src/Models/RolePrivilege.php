<?php

namespace NahidFerdous\Shield\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\Pivot;
use NahidFerdous\Shield\Support\ShieldCache;

class RolePrivilege extends Pivot
{
    use HasFactory;

    protected $table = 'privilege_role';

    protected $fillable = ['role_id', 'privilege_id'];

    public $timestamps = true;

    protected static function booted(): void
    {
        static::saved(function (self $pivot): void {
            ShieldCache::forgetUsersByRoleIds([$pivot->role_id]);
        });

        static::deleted(function (self $pivot): void {
            ShieldCache::forgetUsersByRoleIds([$pivot->role_id]);
        });
    }

    public function getTable()
    {
        return config('shield.tables.role_privilege', parent::getTable());
    }
}
