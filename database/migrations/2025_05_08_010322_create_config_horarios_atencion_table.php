<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateConfigHorariosAtencionTable extends Migration
{
    public function up(): void
    {
        Schema::create('config_horarios_atencion', function (Blueprint $table) {
            $table->id();
            $table->string('dia'); // Lunes, Martes, etc.
            $table->time('hora_inicio');
            $table->time('hora_fin');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('config_horarios_atencion');
    }
}

