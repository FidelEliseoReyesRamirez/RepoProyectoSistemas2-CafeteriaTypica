<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\Combo;
use App\Models\Producto;

class DetalleCombo extends Model
{
    use HasFactory;

    protected $table = 'detallecombo';
    protected $primaryKey = 'id_detallecombo';

    protected $fillable = [
        'id_combo',
        'id_producto',
        'cantidad',
    ];

    public function combo()
    {
        return $this->belongsTo(Combo::class, 'id_combo');
    }

    public function producto()
    {
        return $this->belongsTo(Producto::class, 'id_producto');
    }
}
