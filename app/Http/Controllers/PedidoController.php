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
use App\Models\ConfigEstadoPedido;
use App\Models\ConfigHorarioAtencion;
use Carbon\Carbon;
use App\Models\Pago;
use Illuminate\Support\Facades\Log;

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

        // Validar horario de atención
        $ahora = Carbon::now();
        $dia = ucfirst(strtolower($ahora->locale('es')->isoFormat('dddd')));
        $hora = $ahora->format('H:i:s');

        $horario = ConfigHorarioAtencion::where('dia', $dia)->first();

        if (!$horario || $hora < $horario->hora_inicio || $hora > $horario->hora_fin) {
            return redirect()->back()->withErrors([
                'fuera_horario' => 'No se pueden realizar pedidos fuera del horario de atención.',
            ]);
        }

        try {
            DB::transaction(function () use ($request) {
                $estadoInicial = Estadopedido::where('nombre_estado', 'Pendiente')
                    ->where('eliminado', 0)
                    ->firstOrFail();

                $pedido = Pedido::create([
                    'id_usuario_mesero' => Auth::id(),
                    'estado_actual' => $estadoInicial->id_estado,
                    'fecha_hora_registro' => now(),
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

        $config = ConfigEstadoPedido::all();


        $horarios = ConfigHorarioAtencion::all()->mapWithKeys(function ($item) {
            return [
                strtolower($item->dia) => [
                    'hora_inicio' => $item->hora_inicio,
                    'hora_fin' => $item->hora_fin,
                ],
            ];
        });

        return Inertia::render('order/MyOrders', [
            'orders' => $orders,
            'now' => now()->toISOString(),
            'config' => [
                'estados_cancelables' => $config->where('puede_cancelar', true)->pluck('estado')->values(),
                'estados_editables' => $config->where('puede_editar', true)->pluck('estado')->values(),
                'tiempos_por_estado' => $config->mapWithKeys(fn($item) => [
                    $item->estado => [
                        'cancelar' => $item->tiempo_cancelacion_minutos,
                        'editar' => $item->tiempo_edicion_minutos,
                    ]
                ]),
                'horario_atencion' => $horarios,
            ],
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
                $meseroOriginal = $pedido->usuario?->nombre ?? 'Sin asignar';
                $detalleProductos = implode(', ', $productosDescripcion);
                $totalPedidoFormatted = number_format($totalPedido, 2);

                $descripcion = "$admin editó el pedido #{$pedido->id_pedido} con: $detalleProductos (Total: Bs $totalPedidoFormatted)";
                if (Auth::id() !== $pedido->id_usuario_mesero) {
                    $descripcion .= " (Pedido creado originalmente por: $meseroOriginal)";
                }

                $this->registrarAuditoria('Editar pedido', $descripcion);
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
        $meseroOriginal = $pedido->usuario?->nombre ?? 'Sin asignar';

        $descripcion = "$admin canceló el pedido #{$pedido->id_pedido}";
        if (Auth::id() !== $pedido->id_usuario_mesero) {
            $descripcion .= " (Pedido creado originalmente por: $meseroOriginal)";
        }

        Auditorium::create([
            'id_usuario' => Auth::id(),
            'accion' => 'Cancelar pedido',
            'descripcion' => $descripcion,
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
        $meseroOriginal = $pedido->usuario?->nombre ?? 'Sin asignar';

        $descripcion = "$admin reenvió el pedido #{$pedido->id_pedido} a cocina (estado: Pendiente)";
        if (Auth::id() !== $pedido->id_usuario_mesero) {
            $descripcion .= " (Pedido creado originalmente por: $meseroOriginal)";
        }

        Auditorium::create([
            'id_usuario' => Auth::id(),
            'accion' => 'Rehacer pedido',
            'descripcion' => $descripcion,
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
    public function myOrdersJson()
    {
        $userId = Auth::id();

        $orders = Pedido::with(['detallepedidos.producto', 'estadopedido'])
            ->where('id_usuario_mesero', $userId)
            ->orderByDesc('fecha_hora_registro')
            ->get();

        $config = ConfigEstadoPedido::all();

        $horarios = ConfigHorarioAtencion::all()->mapWithKeys(function ($item) {
            return [
                strtolower($item->dia) => [
                    'hora_inicio' => $item->hora_inicio,
                    'hora_fin' => $item->hora_fin,
                ],
            ];
        });

        return response()->json([
            'orders' => $orders,
            'now' => now()->toISOString(),
            'config' => [
                'estados_cancelables' => $config->where('puede_cancelar', true)->pluck('estado')->values(),
                'estados_editables' => $config->where('puede_editar', true)->pluck('estado')->values(),
                'tiempos_por_estado' => $config->mapWithKeys(fn($item) => [
                    $item->estado => [
                        'cancelar' => $item->tiempo_cancelacion_minutos,
                        'editar' => $item->tiempo_edicion_minutos,
                    ]
                ]),
                'horario_atencion' => $horarios,
            ],
        ]);
    }

    public function allOrders()
    {
        $orders = Pedido::with(['detallepedidos.producto', 'estadopedido', 'usuario'])
            ->orderByDesc('fecha_hora_registro')
            ->get();
        $orders->transform(function ($pedido) {
            $pedidoArray = $pedido->toArray();
            $pedidoArray['usuario_mesero'] = $pedido->usuario ?? [
                'id_usuario' => null,
                'nombre' => 'Sin asignar'
            ];
            unset($pedidoArray['usuario']);
            return $pedidoArray;
        });

        $config = ConfigEstadoPedido::all();

        $horarios = ConfigHorarioAtencion::all()->mapWithKeys(function ($item) {
            return [
                strtolower($item->dia) => [
                    'hora_inicio' => $item->hora_inicio,
                    'hora_fin' => $item->hora_fin,
                ],
            ];
        });

        return Inertia::render('order/AllOrders', [
            'orders' => $orders,
            'now' => now()->toISOString(),
            'config' => [
                'estados_cancelables' => $config->where('puede_cancelar', true)->pluck('estado')->values(),
                'estados_editables' => $config->where('puede_editar', true)->pluck('estado')->values(),
                'tiempos_por_estado' => $config->mapWithKeys(fn($item) => [
                    $item->estado => [
                        'cancelar' => $item->tiempo_cancelacion_minutos,
                        'editar' => $item->tiempo_edicion_minutos,
                    ]
                ]),
                'horario_atencion' => $horarios,
            ],
        ]);
    }


    public function allOrdersJson()
    {
        $orders = Pedido::with([
            'detallepedidos.producto',
            'estadopedido',
            'usuario:id_usuario,nombre',
        ])
            ->orderByDesc('fecha_hora_registro')
            ->get();

        $orders->transform(function ($pedido) {
            $pedidoArray = $pedido->toArray();
            $pedidoArray['usuario_mesero'] = $pedido->usuario ?? [
                'id_usuario' => null,
                'nombre' => 'Sin asignar'
            ];
            unset($pedidoArray['usuario']);
            return $pedidoArray;
        });

        $config = ConfigEstadoPedido::all();

        $horarios = ConfigHorarioAtencion::all()->mapWithKeys(function ($item) {
            return [
                strtolower($item->dia) => [
                    'hora_inicio' => $item->hora_inicio,
                    'hora_fin' => $item->hora_fin,
                ],
            ];
        });

        return response()->json([
            'orders' => $orders,
            'now' => now()->toISOString(),
            'config' => [
                'estados_cancelables' => $config->where('puede_cancelar', true)->pluck('estado')->values(),
                'estados_editables' => $config->where('puede_editar', true)->pluck('estado')->values(),
                'tiempos_por_estado' => $config->mapWithKeys(fn($item) => [
                    $item->estado => [
                        'cancelar' => $item->tiempo_cancelacion_minutos,
                        'editar' => $item->tiempo_edicion_minutos,
                    ]
                ]),
                'horario_atencion' => $horarios,
            ],
        ]);
    }
    public function marcarComoPagado(Request $request, $id)
    {
        $request->validate([
            'metodo_pago' => ['required', 'in:Efectivo,Tarjeta,QR'],
        ]);

        $pedido = Pedido::with('estadopedido')->findOrFail($id);

        if ($pedido->estadopedido->nombre_estado === 'Pagado') {
            return response()->json(['error' => 'Este pedido ya ha sido pagado.'], 422);
        }

        $estadoPagado = Estadopedido::where('nombre_estado', 'Pagado')->firstOrFail();

        DB::transaction(function () use ($pedido, $request, $estadoPagado) {
            $pedido->estado_actual = $estadoPagado->id_estado;
            $pedido->save();

            Pago::create([
                'id_pedido' => $pedido->id_pedido,
                'monto' => $pedido->detallepedidos->sum(fn($d) => $d->cantidad * $d->precio_unitario),
                'metodo_pago' => $request->metodo_pago,
                'fecha_pago' => now(),
                'eliminado' => false,
            ]);

            $usuario = Auth::user()->nombre;
            $descripcion = "$usuario marcó como pagado el pedido #{$pedido->id_pedido} usando {$request->metodo_pago}";
            $this->registrarAuditoria('Pago de pedido', $descripcion);
        });

        return response()->json([
            'nuevo_estado' => [
                'nombre_estado' => $estadoPagado->nombre_estado,
                'color_codigo' => $estadoPagado->color_codigo
            ]
        ]);
    }

    public function vistaCajero()
    {
        $orders = Pedido::with(['detallepedidos.producto', 'estadopedido', 'usuario'])
            ->orderByDesc('fecha_hora_registro')
            ->get();

        $orders->transform(function ($pedido) {
            $pedidoArray = $pedido->toArray();
            $pedidoArray['usuario_mesero'] = $pedido->usuario ?? [
                'id_usuario' => null,
                'nombre' => 'Sin asignar',
            ];
            unset($pedidoArray['usuario']);
            return $pedidoArray;
        });

        return Inertia::render('order/CashierOrders', [
            'orders' => $orders,
            'now' => now()->toISOString(),
        ]);
    }
    public function marcarComoNoPagado($id)
    {
        $pedido = Pedido::with('estadopedido')->findOrFail($id);

        if ($pedido->estadopedido->nombre_estado !== 'Pagado') {
            return back()->with('error', 'El pedido no está marcado como pagado.');
        }

        DB::transaction(function () use ($pedido) {
            $estadoModificado = Estadopedido::where('nombre_estado', 'Modificado')->firstOrFail();
            $pedido->estado_actual = $estadoModificado->id_estado;
            $pedido->save();

            $admin = Auth::user()->nombre;
            $this->registrarAuditoria('Rehacer pago', "$admin marcó como NO pagado el pedido #{$pedido->id_pedido}");
        });

        $nuevoEstado = Estadopedido::select('id_estado', 'nombre_estado', 'color_codigo')
            ->where('nombre_estado', 'Modificado')->first();

        return response()->json([
            'nuevo_estado' => $nuevoEstado,
        ]);
    }
    public function vistaCierreCaja()
    {
        $fechas = Pedido::selectRaw('DATE(fecha_hora_registro) as fecha')
            ->distinct()
            ->orderBy('fecha', 'desc')
            ->pluck('fecha');

        return Inertia::render('order/CierreCaja', [
            'fechas' => $fechas,
        ]);
    }

    public function resumenPorFecha($inicio, $fin = null)
    {
        $query = Pedido::with('pago')
            ->whereHas('pago')
            ->when($fin, fn($q) => $q->whereBetween('fecha_hora_registro', [
                Carbon::parse($inicio)->startOfDay(),
                Carbon::parse($fin)->endOfDay(),
            ]))
            ->when(!$fin, fn($q) => $q->whereDate('fecha_hora_registro', $inicio));

        $totales = [
            'Efectivo' => 0,
            'Tarjeta' => 0,
            'QR' => 0,
            'Total' => 0,
        ];

        foreach ($query->get() as $pedido) {
            $pago = $pedido->pago;
            if ($pago) {
                $totales[$pago->metodo_pago] += $pago->monto;
                $totales['Total'] += $pago->monto;
            }
        }

        return response()->json($totales);
    }

    public function pedidosPorFecha($inicio, $fin = null)
    {
        $query = Pedido::with(['detallepedidos.producto', 'usuario', 'estadopedido', 'pago'])
            ->whereHas('pago', function ($q) {
                $q->where('eliminado', false);
            })
            ->when($fin, fn($q) => $q->whereBetween('fecha_hora_registro', [$inicio, $fin]))
            ->when(!$fin, fn($q) => $q->whereDate('fecha_hora_registro', $inicio));

        if (request()->filled('metodo')) {
            $query->whereHas('pago', fn($q) => $q->where('metodo_pago', request('metodo')));
        }

        $pedidos = $query->get()->unique('id_pedido')->values();

        return response()->json($pedidos->map(function ($pedido) {
            return [
                'id_pedido' => $pedido->id_pedido,
                'monto' => $pedido->pago->monto ?? 0,
                'fecha' => $pedido->fecha_hora_registro,
                'detalles' => $pedido->detallepedidos->map(function ($detalle) {
                    return [
                        'producto' => $detalle->producto->nombre,
                        'cantidad' => $detalle->cantidad,
                        'comentario' => $detalle->comentario,
                        'precio' => $detalle->precio_unitario,
                    ];
                }),
            ];
        }));
    }
    public function cambiarEstado(Request $request, $id)
    {
        $request->validate([
            'estado_id' => ['required', 'exists:estadopedido,id_estado'],
        ]);

        $pedido = Pedido::findOrFail($id);
        $pedido->update(['estado_actual' => $request->estado_id]);

        $nuevo = Estadopedido::findOrFail($request->estado_id);
        $this->registrarAuditoria(
            'Cambiar estado',
            Auth::user()->nombre
                . " cambió el estado del pedido #{$pedido->id_pedido} a {$nuevo->nombre_estado}"
        );

        return response()->json([
            'nombre_estado' => $nuevo->nombre_estado,
            'color_codigo'  => $nuevo->color_codigo,
        ]);
    }
    public function getEstadosPedido()
    {
        $estados = Estadopedido::select('id_estado', 'nombre_estado', 'color_codigo')->get();
        return response()->json($estados);
    }
    public function restaurarRechazado($id)
    {
        $pedido = Pedido::with('estadopedido')->findOrFail($id);

        if ($pedido->estadopedido->nombre_estado !== 'Rechazado') {
            return back()->with('error', 'Solo se pueden restaurar pedidos rechazados.');
        }

        $estadoPendiente = Estadopedido::where('nombre_estado', 'Pendiente')->firstOrFail();

        $pedido->update(['estado_actual' => $estadoPendiente->id_estado]);

        $admin = Auth::user()?->nombre ?? 'Usuario';
        $meseroOriginal = $pedido->usuario?->nombre ?? 'Sin asignar';

        $descripcion = "$admin restauró el pedido #{$pedido->id_pedido} desde estado Rechazado a Pendiente";
        if (Auth::id() !== $pedido->id_usuario_mesero) {
            $descripcion .= " (Pedido creado originalmente por: $meseroOriginal)";
        }

        Auditorium::create([
            'id_usuario' => Auth::id(),
            'accion' => 'Restaurar rechazado',
            'descripcion' => $descripcion,
            'fecha_hora' => now(),
            'eliminado' => 0,
        ]);

        return response()->json(['success' => true]);
    }
    public function rechazarConMotivo(Request $request, $id)
    {
        Log::info("Intentando rechazar pedido ID: $id");
        $pedido = Pedido::with(['detallepedidos' => function ($q) {
            $q->orderBy('id_detalle');
        }])->findOrFail($id);

        if ($pedido->estadopedido->nombre_estado === 'Rechazado') {
            return response()->json(['error' => 'Ya está rechazado'], 422);
        }

        $estadoRechazado = Estadopedido::where('nombre_estado', 'Rechazado')->firstOrFail();

        $motivo = trim($request->input('comentario', ''));

        DB::transaction(function () use ($pedido, $estadoRechazado, $motivo) {
            $pedido->estado_actual = $estadoRechazado->id_estado;
            $pedido->save();
            Log::info("Pedido {$pedido->id_pedido} marcado como Rechazado");

            $pedido->load('detallepedidos');
            $primerDetalle = $pedido->detallepedidos->first();
            if ($primerDetalle && $motivo !== '') {
                $comentarioOriginal = $primerDetalle->comentario ?? '';
                $primerDetalle->comentario = $comentarioOriginal . "\nMotivo rechazo: " . $motivo;
                $primerDetalle->save();
            }

            $usuario = Auth::user()->nombre ?? 'Usuario';
            $descripcion = "$usuario rechazó el pedido #{$pedido->id_pedido}" .
                ($motivo ? " con motivo: $motivo" : '');
            $this->registrarAuditoria('Rechazar pedido', $descripcion);
        });

        return response()->json(['success' => true]);
    }
}
