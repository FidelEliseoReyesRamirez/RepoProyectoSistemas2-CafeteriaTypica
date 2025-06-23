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
        Schema::create('auditoria', function (Blueprint $table) {
            $table->integer('id_log', true);
            $table->integer('id_usuario')->index('id_usuario');
            $table->string('accion', 100);
            $table->string('descripcion', 500)->nullable();
            $table->dateTime('fecha_hora')->nullable()->useCurrent();
            $table->boolean('eliminado')->nullable()->default(false);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('auditoria');
    }
};
