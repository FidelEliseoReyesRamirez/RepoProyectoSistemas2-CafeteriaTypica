<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Class Detallepedido
 * 
 * @property int $id_detalle
 * @property int $id_pedido
 * @property int $id_producto
 * @property int $cantidad
 * @property string|null $comentario
 * @property float $precio_unitario
 * @property bool|null $eliminado
 * 
 * @property Pedido $pedido
 * @property Producto $producto
 *
 * @package App\Models
 */
class Detallepedido extends Model
{
	protected $table = 'detallepedido';
	protected $primaryKey = 'id_detalle';
	public $timestamps = false;

	protected $casts = [
		'id_pedido' => 'int',
		'id_producto' => 'int',
		'cantidad' => 'int',
		'precio_unitario' => 'float',
		'eliminado' => 'bool'
	];

	protected $fillable = [
		'id_pedido',
		'id_producto',
		'cantidad',
		'comentario',
		'precio_unitario',
		'eliminado'
	];

	public function pedido()
	{
		return $this->belongsTo(Pedido::class, 'id_pedido');
	}

	public function producto()
	{
		return $this->belongsTo(Producto::class, 'id_producto');
	}
}
