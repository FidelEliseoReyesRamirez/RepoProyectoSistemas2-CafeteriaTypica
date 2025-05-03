<?php

namespace App\Http\Controllers;

use App\Models\Producto;
use App\Models\Pedido;
use App\Models\Detallepedido;
use App\Models\Estadopedido;
use App\Models\Auditorium; // <-- AGREGA
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Illuminate\Support\Facades\DB;

class PedidoController extends Controller
{
    public function crear()
    {
        $productos = Producto::with('categorium')
            ->where('disponibilidad', 1)
            ->where('eliminado', 0)
            ->get();

        return Inertia::render('order/Order', [
            'productos' => $productos,
        ]);
    }

    public function guardar(Request $request)
    {
        $request->validate([
            'items' => ['required', 'array', 'min:1'],
            'items.*.id_producto' => ['required', 'exists:producto,id_producto'],
            'items.*.cantidad' => ['required', 'integer', 'min:1'],
            'items.*.comentario' => ['nullable', 'string'],
        ]);

        DB::transaction(function () use ($request) {
            $estadoInicial = Estadopedido::where('nombre_estado', 'Pendiente')->where('eliminado', 0)->firstOrFail();

            $pedido = Pedido::create([
                'id_usuario_mesero' => Auth::id(),
                'estado_actual' => $estadoInicial->id_estado,
            ]);

            $productosDescripcion = [];
            $totalPedido = 0;

            foreach ($request->items as $item) {
                $producto = Producto::findOrFail($item['id_producto']);

                Detallepedido::create([
                    'id_pedido' => $pedido->id_pedido,
                    'id_producto' => $producto->id_producto,
                    'cantidad' => $item['cantidad'],
                    'comentario' => $item['comentario'] ?? null,
                    'precio_unitario' => $producto->precio,
                    'eliminado' => 0,
                ]);

                $productosDescripcion[] = "{$item['cantidad']} x {$producto->nombre}";
                $totalPedido += $producto->precio * $item['cantidad'];
            }

            // Registrar en Auditoría después de todo
            $admin = Auth::user()->nombre;
            $detalleProductos = implode(', ', $productosDescripcion);
            $totalPedidoFormatted = number_format($totalPedido, 2);

            $this->registrarAuditoria(
                'Crear pedido',
                "$admin creó el pedido #{$pedido->id_pedido} con: $detalleProductos (Total: Bs $totalPedidoFormatted)"
            );
        });

        return redirect()->route('order.index')->with('success', 'Pedido registrado correctamente.');
    }

    public function myOrders()
    {
        $userId = Auth::id();
    
        $orders = Pedido::with(['detallepedidos.producto', 'estadopedido'])
            ->where('id_usuario_mesero', $userId)
            ->orderByDesc('fecha_hora_registro') 
            ->get();
    
        return Inertia::render('order/MyOrders', [
            'orders' => $orders,
        ]);
    }
    


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
