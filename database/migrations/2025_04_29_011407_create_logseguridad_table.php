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
        Schema::create('logseguridad', function (Blueprint $table) {
            $table->integer('id_log', true);
            $table->integer('id_usuario')->nullable()->index('id_usuario');
            $table->string('evento', 50)->nullable();
            $table->dateTime('fecha_evento')->nullable()->useCurrent();
            $table->string('descripcion')->nullable();
            $table->boolean('eliminado')->default(false);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('logseguridad');
    }
};
