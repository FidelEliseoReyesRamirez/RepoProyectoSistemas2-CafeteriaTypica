<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ConfigEstadoPedido extends Model
{
    protected $table = 'config_estado_pedidos';

    protected $fillable = [
        'tiempo_cancelacion_minutos',
        'tiempo_edicion_minutos',
        'estado',
        'puede_cancelar',
        'puede_editar',
    ];
}
