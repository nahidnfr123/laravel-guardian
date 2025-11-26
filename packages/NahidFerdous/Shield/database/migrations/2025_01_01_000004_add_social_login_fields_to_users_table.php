<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! config('shield.social.enabled', false)) {
            return;
        }
        $userTable = config('shield.tables.users', 'users');
        Schema::table($userTable, function (Blueprint $table) use ($userTable) {
            if (! Schema::hasColumn($userTable, 'provider_name')) {
                $table->string('provider_name')->nullable();
            }

            if (! Schema::hasColumn($userTable, 'provider_id')) {
                $table->string('provider_id')->nullable()->index();
            }

            if (! Schema::hasColumn($userTable, 'avatar')) {
                $table->string('avatar')->nullable();
            }

            $table->index(['provider', 'provider_id']);
        });
    }

    public function down(): void
    {
        $table = config('shield.tables.users', 'users');

        if (Schema::hasColumns($table, ['provider', 'provider_id', 'avatar'])) {
            Schema::table($table, function (Blueprint $table) {
                $table->dropIndex(['provider', 'provider_id']);
                $table->dropColumn(['provider', 'provider_id', 'avatar']);
            });
        }
    }
};
