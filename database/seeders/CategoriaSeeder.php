<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CategoriaSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('categoria')->insert([
            ['nombre' => 'Bebidas calientes', 'descripcion' => 'Café, té y otras bebidas calientes', 'eliminado' => 0],
            ['nombre' => 'Bebidas frías', 'descripcion' => 'Jugos, gaseosas, aguas saborizadas', 'eliminado' => 0],
            ['nombre' => 'Postres', 'descripcion' => 'Tortas, galletas, helados', 'eliminado' => 0],
            ['nombre' => 'Platos principales', 'descripcion' => 'Comidas fuertes', 'eliminado' => 0],
            ['nombre' => 'Snacks', 'descripcion' => 'Porciones pequeñas para picar', 'eliminado' => 0],
            ['nombre' => 'Métodos de preparación', 'descripcion' => 'Métodos manuales de extracción de café', 'eliminado' => 0],
            ['nombre' => 'Cafés fríos', 'descripcion' => 'Cafés preparados en frío o con hielo', 'eliminado' => 0],
            ['nombre' => 'Infusiones y bebidas especiales', 'descripcion' => 'Infusiones especiales, té, chocolates y otras bebidas', 'eliminado' => 0],
            ['nombre' => 'Desayunos', 'descripcion' => 'Opciones de desayuno variadas', 'eliminado' => 0],
            ['nombre' => 'Sandwiches', 'descripcion' => 'Sandwiches artesanales', 'eliminado' => 0],
            ['nombre' => 'Ensaladas', 'descripcion' => 'Ensaladas frescas y saludables', 'eliminado' => 0],
            ['nombre' => 'Empanadas', 'descripcion' => 'Empanadas artesanales', 'eliminado' => 0],
            ['nombre' => 'Platos especiales', 'descripcion' => 'Platos típicos y especiales de la casa', 'eliminado' => 0],
            ['nombre' => 'Cocktails', 'descripcion' => 'Cocktails con café y singani', 'eliminado' => 0],
            ['nombre' => 'Cervezas', 'descripcion' => 'Variedad de cervezas artesanales', 'eliminado' => 0],
            ['nombre' => 'Vinos', 'descripcion' => 'Selección de vinos', 'eliminado' => 0],
        ]);
    }
}
