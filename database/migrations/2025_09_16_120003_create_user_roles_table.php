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
        Schema::create('user_roles', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('role_id');
            $table->unsignedBigInteger('company_id'); // scope por compañía
            $table->timestamp('assigned_at')->useCurrent();
            $table->timestamp('expires_at')->nullable(); // roles temporales
            $table->unsignedBigInteger('assigned_by')->nullable(); // quién asignó el rol
            $table->json('settings')->nullable(); // configuraciones específicas del usuario-rol
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            // Relaciones
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('role_id')->references('id')->on('roles')->onDelete('cascade');
            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
            $table->foreign('assigned_by')->references('id')->on('users')->onDelete('set null');

            // Constraint único: un rol por usuario por compañía
            $table->unique(['user_id', 'role_id', 'company_id']);

            // Índices
            $table->index(['user_id', 'company_id']);
            $table->index(['role_id', 'company_id']);
            $table->index(['is_active', 'expires_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_roles');
    }
};
