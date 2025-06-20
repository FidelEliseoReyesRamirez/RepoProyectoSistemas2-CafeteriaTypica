<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateComboTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::create('combo', function (Blueprint $table) {
            $table->id('id_combo');
            $table->string('nombre', 100);
            $table->text('descripcion')->nullable();
            $table->decimal('precio', 10, 2);
            $table->boolean('disponibilidad')->default(1);
            $table->boolean('eliminado')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('combo');
    }
};
