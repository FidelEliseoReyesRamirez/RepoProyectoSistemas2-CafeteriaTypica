<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

/**
 * Class Prediccion
 * 
 * @property int $id_prediccion
 * @property int $id_producto
 * @property Carbon $fecha_predicha
 * @property Carbon|null $fecha_generada
 * @property int $demanda_prevista
 * @property string|null $tipo_sugerencia
 * @property string|null $sugerencia_descripcion
 * @property bool|null $aceptado
 * @property int|null $id_usuario_accion
 * @property bool|null $eliminado
 * 
 * @property Producto $producto
 * @property Usuario|null $usuario
 *
 * @package App\Models
 */
class Prediccion extends Model
{
	protected $table = 'prediccion';
	protected $primaryKey = 'id_prediccion';
	public $timestamps = false;

	protected $casts = [
		'id_producto' => 'int',
		'fecha_predicha' => 'datetime',
		'fecha_generada' => 'datetime',
		'demanda_prevista' => 'int',
		'aceptado' => 'bool',
		'id_usuario_accion' => 'int',
		'eliminado' => 'bool'
	];

	protected $fillable = [
		'id_producto',
		'fecha_predicha',
		'fecha_generada',
		'demanda_prevista',
		'tipo_sugerencia',
		'sugerencia_descripcion',
		'aceptado',
		'id_usuario_accion',
		'eliminado'
	];

	public function producto()
	{
		return $this->belongsTo(Producto::class, 'id_producto');
	}

	public function usuario()
	{
		return $this->belongsTo(Usuario::class, 'id_usuario_accion');
	}
}
