<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * Class Pedido
 * 
 * @property int $id_pedido
 * @property int $id_usuario_mesero
 * @property Carbon|null $fecha_hora_registro
 * @property int $estado_actual
 * @property bool|null $eliminado
 * 
 * @property Usuario $usuario
 * @property Estadopedido $estadopedido
 * @property Collection|Detallepedido[] $detallepedidos
 * @property Collection|Historialestado[] $historialestados
 * @property Collection|Pago[] $pagos
 * @property Pago $pago
 */
class Pedido extends Model
{
    protected $table = 'pedido';
    protected $primaryKey = 'id_pedido';
    public $timestamps = false;
    public $usuario_mesero;

    protected $casts = [
        'id_usuario_mesero' => 'int',
        'fecha_hora_registro' => 'datetime',
        'estado_actual' => 'int',
        'eliminado' => 'bool'
    ];

    protected $fillable = [
        'id_usuario_mesero',
        'fecha_hora_registro',
        'estado_actual',
        'eliminado'
    ];

    /**
     * @return BelongsTo<Usuario, Pedido>
     */
    public function usuario(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'id_usuario_mesero');
    }

    /**
     * @return BelongsTo<Estadopedido, Pedido>
     */
    public function estadopedido(): BelongsTo
    {
        return $this->belongsTo(Estadopedido::class, 'estado_actual', 'id_estado')
            ->select(['id_estado', 'nombre_estado', 'color_codigo']);
    }

    /**
     * @return HasMany<Detallepedido>
     */
    public function detallepedidos(): HasMany
    {
        return $this->hasMany(Detallepedido::class, 'id_pedido');
    }

    /**
     * @return HasMany<Historialestado>
     */
    public function historialestados(): HasMany
    {
        return $this->hasMany(Historialestado::class, 'id_pedido');
    }

    /**
     * @return HasMany<Pago>
     */
    public function pagos(): HasMany
    {
        return $this->hasMany(Pago::class, 'id_pedido');
    }

    /**
     * @return HasOne<Pago>
     */
    public function pago(): HasOne
    {
        return $this->hasOne(Pago::class, 'id_pedido');
    }
}
