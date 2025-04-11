<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use App\Models\Rol;

class User extends Authenticatable
{
    use Notifiable;

    protected $table = 'usuario';
    protected $primaryKey = 'id_usuario';
    public $timestamps = false;

    protected $fillable = [
        'nombre',
        'email', // <--- este debe coincidir con el nuevo campo de tu base
        'contrasena_hash',
        'id_rol',
        'estado',
    ];

    protected $hidden = ['contrasena_hash'];

    public function getAuthPassword()
    {
        return $this->contrasena_hash;
    }

    public function rol()
    {
        return $this->belongsTo(Rol::class, 'id_rol', 'id_rol');
    }
}
