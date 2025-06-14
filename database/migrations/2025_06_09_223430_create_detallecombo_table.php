<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateDetallecomboTable extends Migration
{
    
     public function up()
    {
        Schema::create('detallecombo', function (Blueprint $table) {
            $table->id('id_detallecombo');
            $table->unsignedBigInteger('id_combo');
            $table->unsignedBigInteger('id_producto');
            $table->integer('cantidad')->default(1);
            $table->timestamps();

            $table->foreign('id_combo')->references('id_combo')->on('combo');
            $table->foreign('id_producto')->references('id_producto')->on('producto');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('detallecombo');
    }
};
