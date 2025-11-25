<?php

namespace NahidFerdous\Guardian\Http\Controllers;

use Illuminate\Routing\Controller;

class GuardianController extends Controller {
    public function tyro() {
        return response([
            'message' => 'Welcome to Tyro, the zero config API boilerplate with roles and abilities for Laravel Sanctum. Visit https://github.com/NahidFerdous/tyro for documentation.',
        ]);
    }

    public function version() {
        return response([
            'version' => config('guardian.version'),
        ]);
    }
}
