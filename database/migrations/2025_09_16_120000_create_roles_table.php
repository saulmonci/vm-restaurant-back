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
        Schema::create('roles', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // admin, manager, employee, viewer
            $table->string('display_name'); // Administrador, Gerente, Empleado, Visualizador
            $table->text('description')->nullable();
            $table->boolean('is_system_role')->default(false); // true para roles del sistema
            $table->unsignedBigInteger('company_id')->nullable(); // null para roles globales
            $table->json('settings')->nullable(); // configuraciones adicionales del rol
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            // Índices
            $table->index(['company_id', 'name']);
            $table->index(['is_active', 'company_id']);

            // Relaciones
            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');

            // Constraint único: nombre de rol por compañía
            $table->unique(['name', 'company_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('roles');
    }
};
