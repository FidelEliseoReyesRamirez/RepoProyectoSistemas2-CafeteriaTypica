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
        /*Schema::create('producto', function (Blueprint $table) {
            $table->integer('id_producto', true);
            $table->string('nombre', 100);
            $table->string('descripcion')->nullable();
            $table->integer('id_categoria')->nullable()->index('idx_producto_categoria');
            $table->decimal('precio', 10);
            $table->boolean('disponibilidad')->nullable()->default(true);
            $table->integer('cantidad_disponible')->nullable()->default(0);
            $table->text('imagen')->nullable();
            $table->boolean('eliminado')->nullable()->default(false);
        });*/
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('producto');
    }
};
