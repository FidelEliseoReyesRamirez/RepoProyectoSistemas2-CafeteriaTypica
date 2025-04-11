<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

/**
 * Class Categorium
 * 
 * @property int $id_categoria
 * @property string $nombre
 * @property string|null $descripcion
 * @property bool|null $eliminado
 * 
 * @property Collection|Producto[] $productos
 *
 * @package App\Models
 */
class Categorium extends Model
{
	protected $table = 'categoria';
	protected $primaryKey = 'id_categoria';
	public $timestamps = false;

	protected $casts = [
		'eliminado' => 'bool'
	];

	protected $fillable = [
		'nombre',
		'descripcion',
		'eliminado'
	];

	public function productos()
	{
		return $this->hasMany(Producto::class, 'id_categoria');
	}
}
