<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use App\Models\Rol;
use App\Notifications\ResetPasswordCustom;

class User extends Authenticatable
{
    use Notifiable;

    protected $table = 'usuario';
    protected $primaryKey = 'id_usuario';
    public $timestamps = false;
    

    protected $fillable = [
        'nombre',
        'email',
        'contrasena_hash',
        'id_rol',
        'estado',
    ];

    protected $hidden = ['contrasena_hash'];

    // Esta línea desactiva completamente el uso del remember token
    protected $rememberTokenName = null;

    public function getAuthPassword()
    {
        return $this->contrasena_hash;
    }

    public function rol()
    {
        return $this->belongsTo(Rol::class, 'id_rol', 'id_rol');
    }

    public function sendPasswordResetNotification($token)
    {
        $this->notify(new ResetPasswordCustom($token));
    }

    public function setPasswordAttribute($value)
    {
        $this->attributes['contrasena_hash'] = bcrypt($value);
    }
    public function getRememberToken()
    {
        return null;
    }

    public function setRememberToken($value)
    {
        // Bloquear uso del campo
    }

    public function getRememberTokenName()
    {
        return null;
    }
    public function save(array $options = [])
    {
        unset($this->remember_token); 
        return parent::save($options);
    }
}
