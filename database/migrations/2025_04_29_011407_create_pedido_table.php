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
        Schema::create('pedido', function (Blueprint $table) {
            $table->integer('id_pedido', true);
            $table->integer('id_usuario_mesero')->index('idx_pedido_mesero');
            $table->dateTime('fecha_hora_registro')->nullable()->useCurrent();
            $table->integer('estado_actual')->index('idx_pedido_estado');
            $table->boolean('eliminado')->nullable()->default(false);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pedido');
    }
};
