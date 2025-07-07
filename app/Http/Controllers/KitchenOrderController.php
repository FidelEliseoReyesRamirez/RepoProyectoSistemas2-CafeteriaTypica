<?php

namespace App\Http\Controllers;

use App\Models\Pedido;
use Illuminate\Support\Carbon;
use Inertia\Inertia;

class KitchenOrderController extends Controller
{
    public function canceled()
    {
        $orders = Pedido::with(['detallepedidos.producto', 'estadopedido'])
            ->whereHas('estadopedido', fn($q) => $q->where('nombre_estado', 'Cancelado'))
            ->orderByDesc('fecha_hora_registro')
            ->get();

        return Inertia::render('kitchen/KitchenCanceled', ['orders' => $orders]);
    }

    public function delivered()
    {
        $orders = Pedido::with(['detallepedidos.producto', 'estadopedido'])
            ->whereHas('estadopedido', fn($q) => $q->where('nombre_estado', 'Entregado'))
            ->orderByDesc('fecha_hora_registro')
            ->get();

        return Inertia::render('kitchen/KitchenDelivered', ['orders' => $orders]);
    }

    public function completed()
    {
        $orders = Pedido::with(['detallepedidos.producto', 'estadopedido'])
            ->whereHas('estadopedido', fn($q) =>
                $q->whereIn('nombre_estado', ['Entregado', 'Pagado']))
            ->orderByDesc('fecha_hora_registro')
            ->get();

        return Inertia::render('kitchen/KitchenCompleted', ['orders' => $orders]);
    }
}
