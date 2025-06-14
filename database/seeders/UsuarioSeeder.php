<?php

namespace Database\Seeders;

use App\Models\Usuario;
use Illuminate\Database\Seeder;

class UsuarioSeeder extends Seeder
{
    public function run(): void
    {
        $usuarios = [
            [
                'nombre' => 'Fidel Reyes',
                'email' => 'fideleliseoreyesramirez@gmail.com',
                'contrasena_hash' => '$2y$12$AQCAiQsvQwimxi007N8IxOSO1giTU.xGGlHGg8gPWdP35uQ3hdtoW',
                'estado' => 'Activo',
                'id_rol' => 1,
                'bloqueado' => 0,
                'bloqueado_hasta' => null,
                'bloqueos_hoy' => 0,
                'eliminado' => 0,
                'intentos_fallidos' => 0,
            ],
            [
                'nombre' => 'Adriano Pérez',
                'email' => 'adriano@gmail.com',
                'contrasena_hash' => '$2y$12$gtttknBOFhc/unYGAVUVm.Kp2Gp4SGigHxFtTp06q6zfT8.CqD0i.',
                'estado' => 'Activo',
                'id_rol' => 3,
                'bloqueado' => 0,
                'bloqueado_hasta' => null,
                'bloqueos_hoy' => 0,
                'eliminado' => 0,
                'intentos_fallidos' => 0,
            ],
            [
                'nombre' => 'Horacio Díaz',
                'email' => 'horacio@gmail.com',
                'contrasena_hash' => '$2y$12$gtttknBOFhc/unYGAVUVm.Kp2Gp4SGigHxFtTp06q6zfT8.CqD0i.',
                'estado' => 'Activo',
                'id_rol' => 4,
                'bloqueado' => 0,
                'bloqueado_hasta' => null,
                'bloqueos_hoy' => 0,
                'eliminado' => 0,
                'intentos_fallidos' => 0,
            ],
            [
                'nombre' => 'Adriano Daza',
                'email' => 'dazaadriano12@gmail.com',
                'contrasena_hash' => '$2y$12$4vSRw07duWNPwWLx8DSs4./rHbqfus4jdSBWUv433tKpADXN7E.xW',
                'estado' => 'Activo',
                'id_rol' => 1,
                'bloqueado' => 0,
                'bloqueado_hasta' => null,
                'bloqueos_hoy' => 0,
                'eliminado' => 0,
                'intentos_fallidos' => 0,
            ],

        ];

        foreach ($usuarios as $usuario) {
            Usuario::updateOrCreate(
                ['email' => $usuario['email']],
                $usuario
            );
        }
    }
}
