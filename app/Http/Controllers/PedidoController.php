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

                // Validar si hay suficiente stock
                if ($producto->cantidad_disponible < $item['cantidad']) {
                    throw new \Exception("Stock insuficiente para el producto {$producto->nombre}");
                }

                // Crear el detalle
                Detallepedido::create([
                    'id_pedido' => $pedido->id_pedido,
                    'id_producto' => $producto->id_producto,
                    'cantidad' => $item['cantidad'],
                    'comentario' => $item['comentario'] ?? null,
                    'precio_unitario' => $producto->precio,
                    'eliminado' => 0,
                ]);

                // Descontar del stock
                $producto->cantidad_disponible -= $item['cantidad'];
                $producto->save();

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

    public function editar($id)
    {
        $pedido = Pedido::with(['detallepedidos.producto'])->findOrFail($id);
        
        $productos = Producto::with('categorium')
            ->where('disponibilidad', 1)
            ->where('eliminado', 0)
            ->get();
    
        $carrito = $pedido->detallepedidos->map(function ($item) {
            return [
                'id_producto' => $item->producto->id_producto,
                'nombre' => $item->producto->nombre,
                'precio' => $item->producto->precio,
                'cantidad' => $item->cantidad,
                'comentario' => $item->comentario,
            ];
        });
    
        return Inertia::render('order/Order', [
            'productos' => $productos,
            'carritoInicial' => $carrito,
            'pedidoId' => $pedido->id_pedido
        ]);
    }
    
    public function actualizar(Request $request, $id)
    {
        $request->validate([
            'items' => ['required', 'array', 'min:1'],
            'items.*.id_producto' => ['required', 'exists:producto,id_producto'],
            'items.*.cantidad' => ['required', 'integer', 'min:1'],
            'items.*.comentario' => ['nullable', 'string'],
        ]);
    
        DB::transaction(function () use ($request, $id) {
            $pedido = Pedido::with('detallepedidos')->findOrFail($id);
    
            // Revertir stock anterior
            foreach ($pedido->detallepedidos as $detalle) {
                $producto = Producto::find($detalle->id_producto);
                if ($producto) {
                    $producto->cantidad_disponible += $detalle->cantidad;
                    $producto->save();
                }
                $detalle->delete();
            }
    
            $productosDescripcion = [];
            $totalPedido = 0;
    
            foreach ($request->items as $item) {
                $producto = Producto::findOrFail($item['id_producto']);
    
                if ($producto->cantidad_disponible < $item['cantidad']) {
                    throw new \Exception("Stock insuficiente para el producto {$producto->nombre}");
                }
    
                Detallepedido::create([
                    'id_pedido' => $pedido->id_pedido,
                    'id_producto' => $producto->id_producto,
                    'cantidad' => $item['cantidad'],
                    'comentario' => $item['comentario'] ?? null,
                    'precio_unitario' => $producto->precio,
                    'eliminado' => 0,
                ]);
    
                $producto->cantidad_disponible -= $item['cantidad'];
                $producto->save();
    
                $productosDescripcion[] = "{$item['cantidad']} x {$producto->nombre}";
                $totalPedido += $producto->precio * $item['cantidad'];
            }
    
            $admin = Auth::user()->nombre;
            $detalleProductos = implode(', ', $productosDescripcion);
            $totalPedidoFormatted = number_format($totalPedido, 2);
    
            $this->registrarAuditoria(
                'Editar pedido',
                "$admin editó el pedido #{$pedido->id_pedido} con: $detalleProductos (Total: Bs $totalPedidoFormatted)"
            );
        });
    
        return redirect()->route('orders.my')->with('success', 'Pedido actualizado correctamente.');
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
