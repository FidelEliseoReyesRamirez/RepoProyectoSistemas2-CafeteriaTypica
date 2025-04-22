<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

/**
 * Class Estadopedido
 * 
 * @property int $id_estado
 * @property string $nombre_estado
 * @property string $color_codigo
 * @property bool|null $eliminado
 * 
 * @property Collection|Historialestado[] $historialestados
 * @property Collection|Pedido[] $pedidos
 *
 * @package App\Models
 */
class Estadopedido extends Model
{
	protected $table = 'estadopedido';
	protected $primaryKey = 'id_estado';
	public $timestamps = false;

	protected $casts = [
		'eliminado' => 'bool'
	];

	protected $fillable = [
		'nombre_estado',
		'color_codigo',
		'eliminado'
	];

	public function historialestados()
	{
		return $this->hasMany(Historialestado::class, 'id_estado');
	}

	public function pedidos()
	{
		return $this->hasMany(Pedido::class, 'estado_actual');
	}
}
