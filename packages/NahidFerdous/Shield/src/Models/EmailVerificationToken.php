<?php

namespace NahidFerdous\Shield\Models;

use Illuminate\Database\Eloquent\Model;

class EmailVerificationToken extends Model
{
    protected $fillable = [
        'user_id',
        'token',
        'expires_at',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
    ];

    public $timestamps = true;
}
