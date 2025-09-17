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
        Schema::create('permissions', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // menu.view, menu.create, menu.edit, menu.delete
            $table->string('display_name'); // Ver Menú, Crear Menú, etc.
            $table->text('description')->nullable();
            $table->string('module'); // menu, company, users, reports, etc.
            $table->string('action'); // view, create, edit, delete, manage
            $table->boolean('is_system_permission')->default(false); // true para permisos del sistema
            $table->json('metadata')->nullable(); // información adicional del permiso
            $table->timestamps();

            // Índices
            $table->index(['module', 'action']);
            $table->index('is_system_permission');

            // Constraint único: nombre de permiso
            $table->unique('name');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('permissions');
    }
};
