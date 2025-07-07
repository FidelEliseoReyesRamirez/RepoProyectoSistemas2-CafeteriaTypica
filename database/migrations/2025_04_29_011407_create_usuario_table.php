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
        Schema::create('usuario', function (Blueprint $table) {
            $table->integer('id_usuario', true);
            $table->string('nombre', 100);
            $table->string('email', 100)->unique('correo');
            $table->string('contrasena_hash');
            $table->string('estado', 50)->nullable()->default('Activo');
            $table->integer('id_rol')->index('idx_usuario_rol');
            $table->boolean('bloqueado')->nullable()->default(false);
            $table->dateTime('bloqueado_hasta')->nullable();
            $table->integer('bloqueos_hoy')->nullable()->default(0);
            $table->boolean('eliminado')->nullable()->default(false);
            $table->integer('intentos_fallidos')->nullable()->default(0);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('usuario');
    }
};
