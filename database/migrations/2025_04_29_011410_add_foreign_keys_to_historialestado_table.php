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
        Schema::table('historialestado', function (Blueprint $table) {
            $table->foreign(['id_pedido'], 'historialestado_ibfk_1')->references(['id_pedido'])->on('pedido')->onUpdate('restrict')->onDelete('restrict');
            $table->foreign(['id_estado'], 'historialestado_ibfk_2')->references(['id_estado'])->on('estadopedido')->onUpdate('restrict')->onDelete('restrict');
            $table->foreign(['id_usuario_responsable'], 'historialestado_ibfk_3')->references(['id_usuario'])->on('usuario')->onUpdate('restrict')->onDelete('restrict');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('historialestado', function (Blueprint $table) {
            $table->dropForeign('historialestado_ibfk_1');
            $table->dropForeign('historialestado_ibfk_2');
            $table->dropForeign('historialestado_ibfk_3');
        });
    }
};
