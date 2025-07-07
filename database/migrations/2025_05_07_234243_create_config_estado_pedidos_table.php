<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('config_estado_pedidos', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('tiempo_cancelacion_minutos')->default(5);
            $table->unsignedInteger('tiempo_edicion_minutos')->default(10);
            $table->string('estado')->unique();
            $table->boolean('puede_cancelar')->default(false);
            $table->boolean('puede_editar')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('config_estado_pedidos');
    }
};
