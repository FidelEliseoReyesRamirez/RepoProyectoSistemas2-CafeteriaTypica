<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RolSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('rol')->insert([
            [ 'nombre' => 'Administrador', 'descripcion' => 'Acceso completo al sistema', 'eliminado' => 0],
            [ 'nombre' => 'Mesero', 'descripcion' => 'Puede tomar pedidos y ver estado', 'eliminado' => 0],
            [ 'nombre' => 'Cocina', 'descripcion' => 'Puede ver y actualizar estado de pedidos', 'eliminado' => 0],
            [ 'nombre' => 'Cajero', 'descripcion' => 'Puede procesar pagos', 'eliminado' => 0],
        ]);
    }
}
