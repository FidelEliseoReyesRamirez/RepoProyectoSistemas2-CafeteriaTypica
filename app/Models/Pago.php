<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

/**
 * Class Pago
 * 
 * @property int $id_pago
 * @property int $id_pedido
 * @property float $monto
 * @property string|null $metodo_pago
 * @property Carbon|null $fecha_pago
 * @property bool|null $eliminado
 * 
 * @property Pedido $pedido
 *
 * @package App\Models
 */
class Pago extends Model
{
	protected $table = 'pago';
	protected $primaryKey = 'id_pago';
	public $timestamps = false;

	protected $casts = [
		'id_pedido' => 'int',
		'monto' => 'float',
		'fecha_pago' => 'datetime',
		'eliminado' => 'bool'
	];

	protected $fillable = [
		'id_pedido',
		'monto',
		'metodo_pago',
		'fecha_pago',
		'eliminado'
	];

	public function pedido()
	{
		return $this->belongsTo(Pedido::class, 'id_pedido');
	}
}
