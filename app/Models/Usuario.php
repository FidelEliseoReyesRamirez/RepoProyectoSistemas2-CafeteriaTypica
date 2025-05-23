<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

/**
 * Class Usuario
 * 
 * @property int $id_usuario
 * @property string $nombre
 * @property string $correo
 * @property string $contrasena_hash
 * @property string|null $estado
 * @property int $id_rol
 * @property bool|null $eliminado
 * 
 * @property Rol $rol
 * @property Collection|Auditorium[] $auditoria
 * @property Collection|Historialestado[] $historialestados
 * @property Collection|Logseguridad[] $logseguridads
 * @property Collection|Pedido[] $pedidos
 * @property Collection|Prediccion[] $prediccions
 *
 * @property bool $bloqueado
 * @property \Carbon\Carbon|null $bloqueado_hasta
 * @property int $bloqueos_hoy
 * @property int $intentos_fallidos
 * @property \Carbon\Carbon|null $updated_at
 * @package App\Models
 */

class Usuario extends Model
{
	protected $table = 'usuario';
	protected $primaryKey = 'id_usuario';
	public $timestamps = false;

	protected $casts = [
        'id_rol' => 'int',
        'eliminado' => 'bool',
        'bloqueado' => 'bool',
        'bloqueado_hasta' => 'datetime',
        'bloqueos_hoy' => 'int',
        'intentos_fallidos' => 'int',
    ];

	protected $fillable = [
        'nombre',
        'email',
        'contrasena_hash',
        'estado',
        'id_rol',
        'bloqueado',
        'bloqueado_hasta',
        'bloqueos_hoy',
        'intentos_fallidos',
        'eliminado'
    ];

	public function rol()
	{
		return $this->belongsTo(Rol::class, 'id_rol');
	}

	public function auditoria()
	{
		return $this->hasMany(Auditorium::class, 'id_usuario');
	}

	public function historialestados()
	{
		return $this->hasMany(Historialestado::class, 'id_usuario_responsable');
	}

	public function logseguridads()
	{
		return $this->hasMany(Logseguridad::class, 'id_usuario');
	}

	public function pedidos()
	{
		return $this->hasMany(Pedido::class, 'id_usuario_mesero');
	}

	public function prediccions()
	{
		return $this->hasMany(Prediccion::class, 'id_usuario_accion');
	}
}
