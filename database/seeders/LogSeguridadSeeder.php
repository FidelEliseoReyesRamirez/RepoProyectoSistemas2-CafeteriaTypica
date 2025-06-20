<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class LogSeguridadSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
     public function run()
    {
        DB::table('logseguridad')->insert([
            [
                'id_usuario' => 1,
                'evento' => 'Login exitoso',
                'descripcion' => 'El usuario administrador inició sesión correctamente.',
                'fecha_evento' => Carbon::now()
            ],
            [
                'id_usuario' => 2,
                'evento' => 'Intento fallido',
                'descripcion' => 'Intento de inicio de sesión fallido para usuario invitado.',
                'fecha_evento' => Carbon::now()->subMinutes(30)
            ],
            [
                'id_usuario' => 3,
                'evento' => 'Cambio de contraseña',
                'descripcion' => 'El usuario actualizó su contraseña.',
                'fecha_evento' => Carbon::now()->subHours(2)
            ]
        ]);
    }
}
