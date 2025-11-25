<?php

namespace NahidFerdous\Guardian\Models;

use NahidFerdous\Guardian\Support\GuardianCache;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\Pivot;

class UserRole extends Pivot
{
    use HasFactory;

    protected $table = 'user_roles';

    protected $fillable = ['user_id', 'role_id'];

    public $timestamps = true;

    protected static function booted(): void
    {
        static::saved(function (self $pivot): void {
            GuardianCache::forgetUser($pivot->user_id);
        });

        static::deleted(function (self $pivot): void {
            GuardianCache::forgetUser($pivot->user_id);
        });
    }

    public function getTable()
    {
        return config('tyro.tables.pivot', parent::getTable());
    }
}
