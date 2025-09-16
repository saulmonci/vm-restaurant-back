<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Profile fields
            $table->string('display_name')->nullable()->after('name');
            $table->string('timezone', 50)->default('UTC')->after('email_verified_at');
            $table->string('preferred_language', 5)->default('en')->after('timezone');
            $table->string('preferred_currency', 3)->default('USD')->after('preferred_language');

            // User status and role fields
            $table->boolean('is_active')->default(true)->after('preferred_currency');
            $table->boolean('is_admin')->default(false)->after('is_active');

            // Preferences stored as JSON
            $table->json('preferences')->nullable()->after('is_admin');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'display_name',
                'timezone',
                'preferred_language',
                'preferred_currency',
                'is_active',
                'is_admin',
                'preferences'
            ]);
        });
    }
};
