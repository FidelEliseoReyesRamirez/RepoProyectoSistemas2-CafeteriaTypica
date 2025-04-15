<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

/**
 * Class Producto
 * 
 * @property int $id_producto
 * @property string $nombre
 * @property string|null $descripcion
 * @property int|null $id_categoria
 * @property float $precio
 * @property bool|null $disponibilidad
 * @property bool|null $eliminado
 * 
 * @property Categorium|null $categorium
 * @property Collection|Detallepedido[] $detallepedidos
 * @property Collection|Prediccion[] $prediccions
 *
 * @package App\Models
 */
class Producto extends Model
{
	protected $table = 'producto';
	protected $primaryKey = 'id_producto';
	public $timestamps = false;

	protected $casts = [
		'id_categoria' => 'int',
		'precio' => 'float',
		'disponibilidad' => 'bool',
		'imagen' => 'string',
		'eliminado' => 'bool'
	];

	protected $fillable = [
		'nombre',
		'descripcion',
		'id_categoria',
		'precio',
		'disponibilidad',
		'imagen', 
		'eliminado'
	];

	public function categorium()
	{
		return $this->belongsTo(Categorium::class, 'id_categoria');
	}

	public function detallepedidos()
	{
		return $this->hasMany(Detallepedido::class, 'id_producto');
	}

	public function prediccions()
	{
		return $this->hasMany(Prediccion::class, 'id_producto');
	}
}
