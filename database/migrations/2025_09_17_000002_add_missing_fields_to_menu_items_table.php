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
        Schema::table('menu_items', function (Blueprint $table) {
            // Agregar campos faltantes del modelo MenuItem
            $table->boolean('is_available')->default(true)->after('price');
            $table->string('image_url')->nullable()->after('is_available');
            $table->json('ingredients')->nullable()->after('image_url');
            $table->json('allergens')->nullable()->after('ingredients');
            $table->json('nutritional_info')->nullable()->after('allergens');
            $table->integer('preparation_time')->nullable()->after('nutritional_info'); // en minutos
            $table->integer('spice_level')->default(0)->after('preparation_time'); // 0-5 escala
            $table->boolean('is_vegetarian')->default(false)->after('spice_level');
            $table->boolean('is_vegan')->default(false)->after('is_vegetarian');
            $table->boolean('is_gluten_free')->default(false)->after('is_vegan');
            $table->integer('calories')->nullable()->after('is_gluten_free');
            $table->integer('sort_order')->default(0)->after('calories');

            // Agregar índices para mejorar rendimiento
            $table->index(['company_id', 'is_available']);
            $table->index(['category_id', 'sort_order']);
            $table->index(['is_vegetarian', 'is_vegan']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('menu_items', function (Blueprint $table) {
            // Eliminar índices primero
            $table->dropIndex(['company_id', 'is_available']);
            $table->dropIndex(['category_id', 'sort_order']);
            $table->dropIndex(['is_vegetarian', 'is_vegan']);

            // Eliminar columnas
            $table->dropColumn([
                'is_available',
                'image_url',
                'ingredients',
                'allergens',
                'nutritional_info',
                'preparation_time',
                'spice_level',
                'is_vegetarian',
                'is_vegan',
                'is_gluten_free',
                'calories',
                'sort_order'
            ]);
        });
    }
};
