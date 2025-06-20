<?php

/**
 * Created by Reliese Model.
 */
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ConfigHorarioAtencion extends Model
{
    protected $table = 'config_horarios_atencion';

    protected $fillable = [
        'dia', 'hora_inicio', 'hora_fin',
    ];
}
