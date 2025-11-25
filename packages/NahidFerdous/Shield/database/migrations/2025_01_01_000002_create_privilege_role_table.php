<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create(config('shield.tables.role_privilege', 'privilege_role'), function (Blueprint $table) {
            $table->id();
            $table->foreignId('role_id')->constrained(config('shield.tables.roles', 'roles'))->cascadeOnDelete();
            $table->foreignId('privilege_id')->constrained(config('shield.tables.privileges', 'privileges'))->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['role_id', 'privilege_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists(config('shield.tables.role_privilege', 'privilege_role'));
    }
};
