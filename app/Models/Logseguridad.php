<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * Class Logseguridad
 * 
 * @property int $id_log
 * @property int|null $id_usuario
 * @property string|null $evento
 * @property Carbon|null $fecha_evento
 * @property string|null $descripcion
 * @property bool $eliminado
 * 
 * @property Usuario|null $usuario
 */
class Logseguridad extends Model
{
    protected $table = 'logseguridad';
    protected $primaryKey = 'id_log';
    public $timestamps = false;

    protected $casts = [
        'id_usuario' => 'int',
        'fecha_evento' => 'datetime',
        'eliminado' => 'boolean',
    ];

    protected $fillable = [
        'id_usuario',
        'evento',
        'fecha_evento',
        'descripcion',
        'eliminado',
    ];

    public function usuario()
    {
        return $this->belongsTo(Usuario::class, 'id_usuario');
    }

   
    protected static function booted()
    {
        static::addGlobalScope('noEliminados', function (Builder $builder) {
            $builder->where('eliminado', 0);
        });
    }

    public static function withEliminados()
    {
        return static::withoutGlobalScope('noEliminados');
    }
}
