<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

/**
 * Class Logseguridad
 * 
 * @property int $id_log
 * @property int|null $id_usuario
 * @property string|null $evento
 * @property Carbon|null $fecha_evento
 * @property string|null $descripcion
 * 
 * @property Usuario|null $usuario
 *
 * @package App\Models
 */
class Logseguridad extends Model
{
	protected $table = 'logseguridad';
	protected $primaryKey = 'id_log';
	public $timestamps = false;

	protected $casts = [
		'id_usuario' => 'int',
		'fecha_evento' => 'datetime'
	];

	protected $fillable = [
		'id_usuario',
		'evento',
		'fecha_evento',
		'descripcion'
	];

	public function usuario()
	{
		return $this->belongsTo(Usuario::class, 'id_usuario');
	}
}
