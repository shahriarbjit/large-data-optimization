<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
 * Auth owns the users table. Laravel's shipped migration creates it;
 * this adds what the API needs. Loaded by AuthServiceProvider.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            // Normalised to +8801XXXXXXXXX before saving.
            $table->string('phone', 20)->unique()->after('email');
            // user | admin | guest (Modules\Auth\DTOs\UserRole)
            $table->string('role', 20)->default('user')->index()->after('phone');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->dropUnique(['phone']);
            $table->dropIndex(['role']);
            $table->dropColumn(['phone', 'role']);
        });
    }
};
