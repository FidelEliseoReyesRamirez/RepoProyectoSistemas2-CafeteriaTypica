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
                'email' => 'fidelrey000@gmail.com',
                'contrasena_hash' => '$2y$12$YQslkQddUVt6CwoYux7QTuuRRqwAK.hcKDVklJ5YKt1/trDYCwAo2',
                'estado' => 'Activo',
                'id_rol' => 1,
                'bloqueado' => 0,
                'bloqueado_hasta' => null,
                'bloqueos_hoy' => 0,
                'eliminado' => 0,
                'intentos_fallidos' => 0,
            ],
            [
                'nombre' => 'Luis Gómez',
                'email' => 'luis@gmail.com',
                'contrasena_hash' => '$2y$12$LJiHcdMq6YZRve07MijYhegVzmHQ4lVzGJdbAWUNiuCrQs6zcQuti',
                'estado' => 'Activo',
                'id_rol' => 3,
                'bloqueado' => 0,
                'bloqueado_hasta' => null,
                'bloqueos_hoy' => 0,
                'eliminado' => 0,
                'intentos_fallidos' => 0,
            ],
            [
                'nombre' => 'Mario Rojas',
                'email' => 'mario.cocina@gmail.com',
                'contrasena_hash' => 'hash789',
                'estado' => 'Activo',
                'id_rol' => 3,
                'bloqueado' => 0,
                'bloqueado_hasta' => null,
                'bloqueos_hoy' => 0,
                'eliminado' => 0,
                'intentos_fallidos' => 0,
            ],
            [
                'nombre' => 'Carmen Díaz',
                'email' => 'carmen.caja@gmail.com',
                'contrasena_hash' => 'hashabc',
                'estado' => 'Activo',
                'id_rol' => 4,
                'bloqueado' => 0,
                'bloqueado_hasta' => null,
                'bloqueos_hoy' => 0,
                'eliminado' => 0,
                'intentos_fallidos' => 0,
            ],
            [
                'nombre' => 'Andres López',
                'email' => 'andres.mesero@gmail.com',
                'contrasena_hash' => 'hashdef',
                'estado' => 'Activo',
                'id_rol' => 2,
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
