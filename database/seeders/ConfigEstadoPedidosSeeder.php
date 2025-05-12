<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;



class ConfigEstadoPedidosSeeder extends Seeder
{
    public function run(): void
    {
        $estados = [
            ['estado' => 'Pendiente', 'puede_cancelar' => true, 'puede_editar' => true],
            ['estado' => 'En preparación', 'puede_cancelar' => false, 'puede_editar' => true],
            ['estado' => 'Listo para servir', 'puede_cancelar' => false, 'puede_editar' => false],
            ['estado' => 'Entregado', 'puede_cancelar' => false, 'puede_editar' => false],
            ['estado' => 'Cancelado', 'puede_cancelar' => false, 'puede_editar' => false],
            ['estado' => 'Modificado', 'puede_cancelar' => false, 'puede_editar' => false],
            ['estado' => 'Pagado', 'puede_cancelar' => false, 'puede_editar' => false],
        ];

        foreach ($estados as $estado) {
            DB::table('config_estado_pedidos')->insert(array_merge($estado, [
                'tiempo_cancelacion_minutos' => 5,
                'tiempo_edicion_minutos' => 10,
                'created_at' => now(),
                'updated_at' => now(),
            ]));
        }
    }
}
