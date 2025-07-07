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
        Schema::create('prediccion', function (Blueprint $table) {
            $table->integer('id_prediccion', true);
            $table->integer('id_producto')->index('idx_prediccion_producto');
            $table->date('fecha_predicha');
            $table->dateTime('fecha_generada')->nullable()->useCurrent();
            $table->integer('demanda_prevista');
            $table->string('tipo_sugerencia', 50)->nullable();
            $table->string('sugerencia_descripcion')->nullable();
            $table->boolean('aceptado')->nullable()->default(false);
            $table->integer('id_usuario_accion')->nullable()->index('id_usuario_accion');
            $table->boolean('eliminado')->nullable()->default(false);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('prediccion');
    }
};
