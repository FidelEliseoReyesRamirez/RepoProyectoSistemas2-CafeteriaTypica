<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

/**
 * Class Pedido
 * 
 * @property int $id_pedido
 * @property int $id_usuario_mesero
 * @property Carbon|null $fecha_hora_registro
 * @property int $estado_actual
 * @property bool|null $eliminado
 * 
 * @property Usuario $usuario
 * @property Estadopedido $estadopedido
 * @property Collection|Detallepedido[] $detallepedidos
 * @property Collection|Historialestado[] $historialestados
 * @property Collection|Pago[] $pagos
 *
 * @package App\Models
 */
class Pedido extends Model
{
	protected $table = 'pedido';
	protected $primaryKey = 'id_pedido';
	public $timestamps = false;

	protected $casts = [
		'id_usuario_mesero' => 'int',
		'fecha_hora_registro' => 'datetime',
		'estado_actual' => 'int',
		'eliminado' => 'bool'
	];

	protected $fillable = [
		'id_usuario_mesero',
		'fecha_hora_registro',
		'estado_actual',
		'eliminado'
	];

	public function usuario()
	{
		return $this->belongsTo(Usuario::class, 'id_usuario_mesero');
	}

	public function estadopedido()
	{
		return $this->belongsTo(Estadopedido::class, 'estado_actual');
	}

	public function detallepedidos()
	{
		return $this->hasMany(Detallepedido::class, 'id_pedido');
	}

	public function historialestados()
	{
		return $this->hasMany(Historialestado::class, 'id_pedido');
	}

	public function pagos()
	{
		return $this->hasMany(Pago::class, 'id_pedido');
	}
}
