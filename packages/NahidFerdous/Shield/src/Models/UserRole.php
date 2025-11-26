<?php

namespace NahidFerdous\Shield\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\Pivot;
use NahidFerdous\Shield\Support\ShieldCache;

class UserRole extends Pivot
{
    use HasFactory;

    protected $table = 'user_roles';

    protected $fillable = ['user_id', 'role_id'];

    public $timestamps = true;

    protected static function booted(): void
    {
        static::saved(function (self $pivot): void {
            ShieldCache::forgetUser($pivot->user_id);
        });

        static::deleted(function (self $pivot): void {
            ShieldCache::forgetUser($pivot->user_id);
        });
    }

    public function getTable()
    {
        return config('shield.tables.pivot', parent::getTable());
    }
}
