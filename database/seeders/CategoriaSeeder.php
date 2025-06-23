<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CategoriaSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('categoria')->insert([
            [ 'nombre' => 'Bebidas calientes', 'descripcion' => 'Café, té y otras bebidas calientes', 'eliminado' => 0],
            [ 'nombre' => 'Bebidas frías', 'descripcion' => 'Jugos, gaseosas, aguas saborizadas', 'eliminado' => 0],
            [ 'nombre' => 'Postres', 'descripcion' => 'Tortas, galletas, helados', 'eliminado' => 0],
            [ 'nombre' => 'Platos principales', 'descripcion' => 'Comidas fuertes', 'eliminado' => 0],
            [ 'nombre' => 'Snacks', 'descripcion' => 'Porciones pequeñas para picar', 'eliminado' => 0],
        ]);
    }
}
