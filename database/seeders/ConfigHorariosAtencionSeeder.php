<?php

namespace Database\Seeders;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ConfigHorariosAtencionSeeder extends Seeder
{
    public function run(): void
    {
        $dias = [
            'Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado', 'Domingo'
        ];

        foreach ($dias as $dia) {
            DB::table('config_horarios_atencion')->insert([
                'dia' => $dia,
                'hora_inicio' => '08:00:00',
                'hora_fin' => $dia === 'Domingo' ? '14:00:00' : '20:00:00',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}
