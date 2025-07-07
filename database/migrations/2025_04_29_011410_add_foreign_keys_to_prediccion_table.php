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
        Schema::table('prediccion', function (Blueprint $table) {
            $table->foreign(['id_producto'], 'prediccion_ibfk_1')->references(['id_producto'])->on('producto')->onUpdate('restrict')->onDelete('restrict');
            $table->foreign(['id_usuario_accion'], 'prediccion_ibfk_2')->references(['id_usuario'])->on('usuario')->onUpdate('restrict')->onDelete('restrict');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('prediccion', function (Blueprint $table) {
            $table->dropForeign('prediccion_ibfk_1');
            $table->dropForeign('prediccion_ibfk_2');
        });
    }
};
