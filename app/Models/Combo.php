<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Combo extends Model
{
    use HasFactory;

    protected $table = 'combo';
    protected $primaryKey = 'id_combo';

    protected $fillable = [
        'nombre',
        'descripcion',
        'precio',
        'disponibilidad',
        'eliminado',
    ];

    public function detalles()
    {
        return $this->hasMany(DetalleCombo::class, 'id_combo');
    }
}
