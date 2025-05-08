<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Configuracion;

class ConfiguracionSeeder extends Seeder
{
    public function run(): void
    {
        Configuracion::updateOrCreate(['clave' => 'tiempo_cancelacion_minutos'], ['valor' => '5']);
        Configuracion::updateOrCreate(['clave' => 'tiempo_edicion_minutos'], ['valor' => '10']);
        Configuracion::updateOrCreate(['clave' => 'estados_cancelables'], ['valor' => json_encode(['Pendiente'])]);
        Configuracion::updateOrCreate(['clave' => 'estados_editables'], ['valor' => json_encode(['Pendiente', 'Modificado'])]);
    }
}
