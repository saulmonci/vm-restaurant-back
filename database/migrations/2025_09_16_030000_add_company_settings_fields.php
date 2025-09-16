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
        Schema::table('companies', function (Blueprint $table) {
            // Company branding and customization
            $table->string('logo_url')->nullable()->after('phone');
            $table->string('website')->nullable()->after('logo_url');

            // Localization settings
            $table->string('timezone', 50)->default('UTC')->after('website');
            $table->string('currency', 3)->default('USD')->after('timezone');
            $table->string('language', 5)->default('en')->after('currency');

            // Custom settings stored as JSON
            $table->json('settings')->nullable()->after('language');

            // Additional business info
            $table->text('business_hours')->nullable()->after('settings')->comment('JSON with detailed business hours');
            $table->decimal('tax_rate', 5, 2)->nullable()->after('business_hours');
            $table->string('business_type', 50)->nullable()->after('tax_rate');

            // Social media links
            $table->string('facebook_url')->nullable()->after('business_type');
            $table->string('instagram_url')->nullable()->after('facebook_url');
            $table->string('twitter_url')->nullable()->after('instagram_url');

            // Subscription/plan info
            $table->string('subscription_plan', 20)->default('basic')->after('twitter_url');
            $table->timestamp('subscription_expires_at')->nullable()->after('subscription_plan');

            // Additional operational settings
            $table->boolean('auto_accept_orders')->default(false)->after('subscription_expires_at');
            $table->integer('max_preparation_time')->default(30)->after('auto_accept_orders')->comment('minutes');
            $table->decimal('service_fee_percentage', 5, 2)->default(0)->after('max_preparation_time');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->dropColumn([
                'logo_url',
                'website',
                'timezone',
                'currency',
                'language',
                'settings',
                'business_hours',
                'tax_rate',
                'business_type',
                'facebook_url',
                'instagram_url',
                'twitter_url',
                'subscription_plan',
                'subscription_expires_at',
                'auto_accept_orders',
                'max_preparation_time',
                'service_fee_percentage'
            ]);
        });
    }
};
