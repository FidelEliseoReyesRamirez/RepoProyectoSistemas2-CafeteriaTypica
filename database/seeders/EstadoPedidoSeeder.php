<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class EstadoPedidoSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('estadopedido')->insert([
            ['nombre_estado' => 'Pendiente', 'color_codigo' => '#FFCE1B', 'eliminado' => 0],
            ['nombre_estado' => 'En preparación', 'color_codigo' => '#fc4b08', 'eliminado' => 0],
            ['nombre_estado' => 'Listo para servir', 'color_codigo' => '#00FF00', 'eliminado' => 0],
            ['nombre_estado' => 'Entregado', 'color_codigo' => '#0000FF', 'eliminado' => 0],
            ['nombre_estado' => 'Cancelado', 'color_codigo' => '#FF0000', 'eliminado' => 0],
            ['nombre_estado' => 'Pagado', 'color_codigo' => '#800080', 'eliminado' => 0],
            ['nombre_estado' => 'Modificado', 'color_codigo' => '#FF69B4', 'eliminado' => 0],
            ['nombre_estado' => 'Rechazado', 'color_codigo' => '#292423', 'eliminado' => 0],
        ]);        
    }
}
