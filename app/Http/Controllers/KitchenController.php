<?php

namespace App\Http\Controllers;

use App\Models\Pedido;
use App\Models\Estadopedido;
use App\Models\Auditorium;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class KitchenController extends Controller
{
    public function vista()
    {
        return Inertia::render('kitchen/KitchenOrders');
    }

    public function index()
    {
        $activos = Pedido::with(['detallepedidos.producto', 'estadopedido'])
            ->whereHas('estadopedido', function ($q) {
                $q->whereIn('nombre_estado', ['Pendiente', 'Modificado', 'En preparación', 'Listo para servir', 'Entregado', 'Rechazado']);
            })

            ->orderBy('fecha_hora_registro')
            ->get()
            ->map(function ($pedido) {
                $arr = $pedido->toArray();
                if ($pedido->estadopedido->nombre_estado === 'Modificado') {
                    $audit = Auditorium::where('accion', 'Editar pedido')
                        ->where('descripcion', 'like', "%pedido #{$pedido->id_pedido}%")
                        ->latest('fecha_hora')
                        ->first();
                    if ($audit && preg_match('/con: (.*) \(Total:/', $audit->descripcion, $m)) {
                        $arr['nuevos_detalles'] = array_map('trim', explode(',', $m[1]));
                    } else {
                        $arr['nuevos_detalles'] = [];
                    }
                } else {
                    $arr['nuevos_detalles'] = [];
                }
                return $arr;
            });

        $cancelados = Pedido::with(['detallepedidos.producto', 'estadopedido'])
            ->whereHas('estadopedido', function ($q) {
                $q->where('nombre_estado', 'Cancelado');
            })
            ->orderByDesc('fecha_hora_registro')
            ->get()
            ->map(function ($pedido) {
                $arr = $pedido->toArray();
                $arr['nuevos_detalles'] = [];
                return $arr;
            });

        return response()->json([
            'activos'    => $activos,
            'cancelados' => $cancelados,
        ]);
    }



    public function cambiarEstado(Request $request, $id)
    {
        $request->validate([
            'estado' => 'required|string',
        ]);

        $pedido = Pedido::findOrFail($id);

        $nuevoEstado = Estadopedido::where('nombre_estado', $request->estado)->firstOrFail();
        $pedido->estado_actual = $nuevoEstado->id_estado;
        $pedido->save();

        // Registrar auditoría
        $usuario = Auth::user()->nombre;
        $descripcion = "{$usuario} cambió el estado del pedido #{$pedido->id_pedido} a {$nuevoEstado->nombre_estado}";
        $this->registrarAuditoria('Cambiar estado pedido', $descripcion);

        return response()->json(['success' => true]);
    }

    /**
     * Registra una entrada de auditoría en la tabla Auditorium.
     *
     * @param string $accion
     * @param string $descripcion
     * @return void
     */
    private function registrarAuditoria(string $accion, string $descripcion): void
    {
        Auditorium::create([
            'id_usuario' => Auth::id(),
            'accion' => $accion,
            'descripcion' => $descripcion,
            'fecha_hora' => now(),
            'eliminado' => 0,
        ]);
    }
}
