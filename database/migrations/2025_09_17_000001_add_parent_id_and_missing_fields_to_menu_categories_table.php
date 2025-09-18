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
        Schema::table('menu_categories', function (Blueprint $table) {
            // Agregar parent_id para jerarquía
            $table->unsignedBigInteger('parent_id')->nullable()->after('company_id');

            // Agregar campos faltantes del modelo
            $table->text('description')->nullable()->after('name');
            $table->boolean('is_active')->default(true)->after('description');
            $table->integer('sort_order')->default(0)->after('is_active');
            $table->string('image_url')->nullable()->after('sort_order');

            // Agregar foreign key para parent_id
            $table->foreign('parent_id')->references('id')->on('menu_categories')->onDelete('cascade');

            // Agregar índices para mejorar rendimiento
            $table->index(['company_id', 'parent_id']);
            $table->index(['is_active', 'sort_order']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('menu_categories', function (Blueprint $table) {
            // Eliminar foreign key primero
            $table->dropForeign(['parent_id']);

            // Eliminar índices
            $table->dropIndex(['company_id', 'parent_id']);
            $table->dropIndex(['is_active', 'sort_order']);

            // Eliminar columnas
            $table->dropColumn([
                'parent_id',
                'description',
                'is_active',
                'sort_order',
                'image_url'
            ]);
        });
    }
};
