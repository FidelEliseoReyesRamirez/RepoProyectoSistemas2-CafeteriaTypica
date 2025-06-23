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
       /* Schema::create('detallepedido', function (Blueprint $table) {
            $table->integer('id_detalle', true);
            $table->integer('id_pedido');
            $table->integer('id_producto')->index('id_producto');
            $table->integer('cantidad');
            $table->string('comentario')->nullable();
            $table->decimal('precio_unitario', 10);
            $table->boolean('eliminado')->nullable()->default(false);

            $table->index(['id_pedido', 'id_producto'], 'idx_detallepedido');
        });*/
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('detallepedido');
    }
};
