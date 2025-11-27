<?php

namespace NahidFerdous\Shield\Http\Controllers;

use Illuminate\Routing\Controller;
use NahidFerdous\Shield\Traits\ApiResponseTrait;

class ShieldController extends Controller
{
    use ApiResponseTrait;

    public function shield()
    {
        return response([
            'message' => 'Welcome to Shield, the zero config API boilerplate with roles and abilities for Laravel Sanctum. Visit https://github.com/nahidnfr123/laravel-shield for documentation.',
        ]);
    }

    public function version()
    {
        return response([
            'version' => config('shield.version'),
        ]);
    }
}
