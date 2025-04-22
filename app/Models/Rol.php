<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

/**
 * Class Rol
 * 
 * @property int $id_rol
 * @property string $nombre
 * @property string|null $descripcion
 * @property bool|null $eliminado
 * 
 * @property Collection|Usuario[] $usuarios
 *
 * @package App\Models
 */
class Rol extends Model
{
	protected $table = 'rol';
	protected $primaryKey = 'id_rol';
	public $timestamps = false;

	protected $casts = [
		'eliminado' => 'bool'
	];

	protected $fillable = [
		'nombre',
		'descripcion',
		'eliminado'
	];

	public function usuarios()
	{
		return $this->hasMany(User::class, 'id_rol');
	}
}
