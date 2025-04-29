<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            AuditoriaSeeder::class,
            CategoriaSeeder::class,
            DetallePedidoSeeder::class,
            EstadoPedidoSeeder::class,
            HistorialEstadoSeeder::class,
            LogSeguridadSeeder::class,
            PagoSeeder::class,
            PedidoSeeder::class,
            PrediccionSeeder::class,
            ProductoSeeder::class,
            RolSeeder::class,
            UsuarioSeeder::class,
        ]);
    }
}
