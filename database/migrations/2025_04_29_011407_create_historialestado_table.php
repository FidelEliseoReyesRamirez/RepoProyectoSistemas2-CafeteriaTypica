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
        Schema::create('historialestado', function (Blueprint $table) {
            $table->integer('id_historial', true);
            $table->integer('id_pedido')->index('idx_historialestado_pedido');
            $table->integer('id_estado')->index('id_estado');
            $table->integer('id_usuario_responsable')->index('id_usuario_responsable');
            $table->dateTime('fecha_hora_cambio')->nullable();
            $table->boolean('eliminado')->nullable()->default(false);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('historialestado');
    }
};
