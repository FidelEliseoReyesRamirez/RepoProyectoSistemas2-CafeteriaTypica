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
        Schema::table('pago', function (Blueprint $table) {
            $table->foreign(['id_pedido'], 'pago_ibfk_1')->references(['id_pedido'])->on('pedido')->onUpdate('restrict')->onDelete('restrict');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pago', function (Blueprint $table) {
            $table->dropForeign('pago_ibfk_1');
        });
    }
};
