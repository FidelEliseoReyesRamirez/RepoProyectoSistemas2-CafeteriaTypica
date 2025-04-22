<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

/**
 * Class Historialestado
 * 
 * @property int $id_historial
 * @property int $id_pedido
 * @property int $id_estado
 * @property int $id_usuario_responsable
 * @property Carbon|null $fecha_hora_cambio
 * @property bool|null $eliminado
 * 
 * @property Pedido $pedido
 * @property Estadopedido $estadopedido
 * @property Usuario $usuario
 *
 * @package App\Models
 */
class Historialestado extends Model
{
	protected $table = 'historialestado';
	protected $primaryKey = 'id_historial';
	public $timestamps = false;

	protected $casts = [
		'id_pedido' => 'int',
		'id_estado' => 'int',
		'id_usuario_responsable' => 'int',
		'fecha_hora_cambio' => 'datetime',
		'eliminado' => 'bool'
	];

	protected $fillable = [
		'id_pedido',
		'id_estado',
		'id_usuario_responsable',
		'fecha_hora_cambio',
		'eliminado'
	];

	public function pedido()
	{
		return $this->belongsTo(Pedido::class, 'id_pedido');
	}

	public function estadopedido()
	{
		return $this->belongsTo(Estadopedido::class, 'id_estado');
	}

	public function usuario()
	{
		return $this->belongsTo(Usuario::class, 'id_usuario_responsable');
	}
}
