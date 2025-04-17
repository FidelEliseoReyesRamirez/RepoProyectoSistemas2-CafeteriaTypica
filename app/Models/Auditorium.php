<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

/**
 * Class Auditorium
 * 
 * @property int $id_log
 * @property int $id_usuario
 * @property string $accion
 * @property string|null $descripcion
 * @property Carbon|null $fecha_hora
 * @property bool|null $eliminado
 * 
 * @property Usuario $usuario
 *
 * @package App\Models
 */
class Auditorium extends Model
{
	protected $table = 'auditoria';
	protected $primaryKey = 'id_log';
	public $timestamps = false;

	protected $casts = [
		'id_usuario' => 'int',
		'fecha_hora' => 'datetime',
		'eliminado' => 'bool'
	];

	protected $fillable = [
		'id_usuario',
		'accion',
		'descripcion',
		'fecha_hora',
		'eliminado'
	];

	public function usuario()
	{
		return $this->belongsTo(Usuario::class, 'id_usuario');
	}
}
