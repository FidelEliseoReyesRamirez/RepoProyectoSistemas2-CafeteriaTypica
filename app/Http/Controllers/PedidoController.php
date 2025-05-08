<?php

namespace App\Http\Controllers;

use App\Models\Producto;
use App\Models\Pedido;
use App\Models\Detallepedido;
use App\Models\Estadopedido;
use App\Models\Auditorium;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use App\Models\Configuracion;

$minutosCancelacion = (int) Configuracion::obtener('tiempo_cancelacion_minutos', 5);
$estadosCancelables = json_decode(Configuracion::obtener('estados_cancelables', '[]'), true);

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

        try {
            DB::transaction(function () use ($request) {
                $estadoInicial = Estadopedido::where('nombre_estado', 'Pendiente')
                    ->where('eliminado', 0)
                    ->firstOrFail();

                $pedido = Pedido::create([
                    'id_usuario_mesero' => Auth::id(),
                    'estado_actual' => $estadoInicial->id_estado,
                ]);

                $productosDescripcion = [];
                $totalPedido = 0;

                foreach ($request->items as $item) {
                    $producto = Producto::findOrFail($item['id_producto']);

                    if ($producto->cantidad_disponible < $item['cantidad']) {
                        throw ValidationException::withMessages([
                            'stock' => "Stock insuficiente para el producto {$producto->nombre}"
                        ]);
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
                    'Crear pedido',
                    "$admin creó el pedido #{$pedido->id_pedido} con: $detalleProductos (Total: Bs $totalPedidoFormatted)"
                );
            });

            return redirect()->route('order.index')->with('success', 'Pedido registrado correctamente.');
        } catch (ValidationException $e) {
            throw $e;
        } catch (\Exception $e) {
            return redirect()->back()->withErrors([
                'stock' => 'Ocurrió un error inesperado. Intente nuevamente.',
            ]);
        }
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

        try {
            DB::transaction(function () use ($request, $id) {
                $pedido = Pedido::with(['detallepedidos'])->findOrFail($id);

                $original = $pedido->detallepedidos->map(function ($d) {
                    return [
                        'id_producto' => $d->id_producto,
                        'cantidad' => $d->cantidad,
                        'comentario' => $d->comentario ?? '',
                    ];
                })->sortBy('id_producto')->values()->toArray();

                $nuevo = collect($request->items)->map(function ($item) {
                    return [
                        'id_producto' => $item['id_producto'],
                        'cantidad' => $item['cantidad'],
                        'comentario' => $item['comentario'] ?? '',
                    ];
                })->sortBy('id_producto')->values()->toArray();

                // Si no hay cambios, no se modifica
                if ($original === $nuevo) {
                    return; // Salimos del transaction sin hacer nada
                }

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
                        throw new \Exception("Stock insuficiente");
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

                $estadoModificado = Estadopedido::where('nombre_estado', 'Modificado')->firstOrFail();
                $pedido->estado_actual = $estadoModificado->id_estado;
                $pedido->save();

                $admin = Auth::user()->nombre;
                $detalleProductos = implode(', ', $productosDescripcion);
                $totalPedidoFormatted = number_format($totalPedido, 2);

                $this->registrarAuditoria(
                    'Editar pedido',
                    "$admin editó el pedido #{$pedido->id_pedido} con: $detalleProductos (Total: Bs $totalPedidoFormatted)"
                );
            });
            return back()->with('success', 'Pedido actualizado correctamente.');

        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Ocurrió un error inesperado. Intente nuevamente.');
        }
    }

    public function cancelar($id)
    {
        $pedido = Pedido::findOrFail($id);

        // Verificar si ya está cancelado o pagado
        $estadoActual = $pedido->estadopedido?->nombre_estado;
        if (in_array($estadoActual, ['Cancelado', 'Pagado'])) {
            return back()->with('error', 'No se puede cancelar un pedido ya pagado o cancelado.');
        }

        // Obtener ID del estado 'Cancelado'
        $estadoCancelado = Estadopedido::where('nombre_estado', 'Cancelado')->firstOrFail();

        // Actualizar el estado
        $pedido->update(['estado_actual' => $estadoCancelado->id_estado]);


        // Registrar auditoría
        $admin = Auth::user()?->nombre ?? 'Usuario';
        Auditorium::create([
            'id_usuario' => Auth::id(),
            'accion' => 'Cancelar pedido',
            'descripcion' => "$admin canceló el pedido #{$pedido->id_pedido}",
            'fecha_hora' => now(),
            'eliminado' => 0,
        ]);

        return back()->with('success', 'Pedido cancelado correctamente.');
    }
    public function rehacer($id)
    {
        $pedido = Pedido::findOrFail($id);

        // Verifica que esté cancelado
        $estadoActual = $pedido->estadopedido?->nombre_estado;
        if ($estadoActual !== 'Cancelado') {
            return back()->with('error', 'Solo se pueden rehacer pedidos cancelados.');
        }

        $estadoPendiente = Estadopedido::where('nombre_estado', 'Pendiente')->firstOrFail();

        $pedido->update(['estado_actual' => $estadoPendiente->id_estado]);

        $admin = Auth::user()?->nombre ?? 'Usuario';
        Auditorium::create([
            'id_usuario' => Auth::id(),
            'accion' => 'Rehacer pedido',
            'descripcion' => "$admin reenvió el pedido #{$pedido->id_pedido} a cocina (estado: Pendiente)",
            'fecha_hora' => now(),
            'eliminado' => 0,
        ]);

        return back()->with('success', 'Pedido reenviado a cocina.');
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
