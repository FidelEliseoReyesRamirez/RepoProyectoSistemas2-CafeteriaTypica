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
        Schema::create('pago', function (Blueprint $table) {
            $table->integer('id_pago', true);
            $table->integer('id_pedido')->index('idx_pago_pedido');
            $table->decimal('monto', 10);
            $table->string('metodo_pago', 50)->nullable();
            $table->dateTime('fecha_pago')->nullable()->useCurrent();
            $table->boolean('eliminado')->nullable()->default(false);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pago');
    }
};
