<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class EstadoPedidoSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('estadopedido')->insert([
            [ 'nombre_estado' => 'Pendiente', 'color_codigo' => '#FFA500', 'eliminado' => 0],
            [ 'nombre_estado' => 'En preparación', 'color_codigo' => '#FFFF00', 'eliminado' => 0],
            [ 'nombre_estado' => 'Listo para servir', 'color_codigo' => '#00FF00', 'eliminado' => 0],
            [ 'nombre_estado' => 'Entregado', 'color_codigo' => '#008000', 'eliminado' => 0],
            [ 'nombre_estado' => 'Cancelado', 'color_codigo' => '#FF0000', 'eliminado' => 0],
            [ 'nombre_estado' => 'Pagado', 'color_codigo' => '#0000FF', 'eliminado' => 0],
        ]);
    }
}
