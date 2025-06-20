<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Log;

class DashboardController extends Controller
{
    public function index()
    {
        // Verificar archivo forecast
        $forecastPath = storage_path('app/forecast.json');
        $forecastExists = file_exists($forecastPath);

        Log::info('Verificando archivo forecast:', [
            'storage_path' => $forecastPath,
            'file_exists' => $forecastExists,
        ]);

        $forecast = [];
        if ($forecastExists) {
            try {
                $forecastContent = file_get_contents($forecastPath);
                $forecast = json_decode($forecastContent, true);
                if (json_last_error() !== JSON_ERROR_NONE) {
                    Log::error('JSON decode error: ' . json_last_error_msg());
                    $forecast = $this->getEmptyForecastStructure();
                }
            } catch (\Exception $e) {
                Log::error('Error reading forecast file: ' . $e->getMessage());
                $forecast = $this->getEmptyForecastStructure();
            }
        } else {
            $forecast = $this->getEmptyForecastStructure();
        }

        // Métricas de la semana actual (SOLO PEDIDOS PAGADOS - estado_actual = 6)
        $ventasSemana = DB::table('detallepedido')
            ->join('pedido', 'detallepedido.id_pedido', '=', 'pedido.id_pedido')
            ->where('pedido.eliminado', 0)
            ->where('detallepedido.eliminado', 0)
            ->where('pedido.estado_actual', 6) // Solo pedidos pagados
            ->whereBetween('pedido.fecha_hora_registro', [now()->startOfWeek(), now()])
            ->sum(DB::raw('detallepedido.cantidad * detallepedido.precio_unitario'));

        $clientes = DB::table('pedido')
            ->where('eliminado', 0)
            ->where('estado_actual', 6) // Solo pedidos pagados
            ->whereBetween('fecha_hora_registro', [now()->startOfWeek(), now()])
            ->count();



        // Ventas por día (últimos 90 días) - SOLO PEDIDOS PAGADOS
        $ventasPorDia = DB::table('pedido')
            ->where('eliminado', 0)
            ->where('estado_actual', 6)
            ->selectRaw('DATE(fecha_hora_registro) as dia, COUNT(*) as ventas')
            ->whereBetween('fecha_hora_registro', [now()->subDays(90), now()])
            ->groupByRaw('DATE(fecha_hora_registro)')
            ->orderBy('dia')
            ->get();

        // Top productos del mes ANTERIOR (no el actual)
        $topProductos = $this->getTopProductosMesAnterior();

        // Debug info
        $totalPedidos = DB::table('pedido')->where('eliminado', 0)->where('estado_actual', 6)->count();
        $totalDetalles = DB::table('detallepedido')->where('eliminado', 0)->count();

        return Inertia::render('Admin/Dashboard', [
            'forecast'                => $forecast['general'] ?? [],
            'porProducto'            => $forecast['por_producto'] ?? [],
            'productoTendencia'      => $forecast['producto_tendencia'] ?? [
                'nombre' => 'N/A',
                'crecimiento' => 0,
                'ventas_actuales' => 0,
                'ventas_anteriores' => 0
            ],
            'productosEstacionales'  => $forecast['productos_estacionales'] ?? [],
            'alertasStock'           => $forecast['alertas_stock'] ?? [],
            'metrics'                => [
                'ventasSemana'           => round($ventasSemana, 2),
                'clientesAtendidos'      => $clientes,
            ],
            'ventasDiarias'          => $ventasPorDia,
            'topProductos'           => $topProductos,

            'debug' => [
                'forecastExists'         => $forecastExists,
                'totalPedidos'          => $totalPedidos,
                'totalDetalles'         => $totalDetalles,
                'forecastGeneralCount'  => count($forecast['general'] ?? []),
                'forecastProductCount'  => count($forecast['por_producto'] ?? [])
            ]
        ]);
    }

    private function getEmptyForecastStructure()
    {
        return [
            'general' => [],
            'por_producto' => [],
            'producto_tendencia' => [
                'nombre' => 'N/A',
                'crecimiento' => 0,
                'ventas_actuales' => 0,
                'ventas_anteriores' => 0
            ],
            'productos_estacionales' => [],
            'alertas_stock' => []
        ];
    }

    private function getTopProductosMesAnterior()
    {
        try {
            // Obtener el mes anterior
            $inicioMesAnterior = now()->startOfMonth()->subMonth();
            $finMesAnterior = now()->startOfMonth()->subDay();

            return DB::table('detallepedido')
                ->join('pedido', 'detallepedido.id_pedido', '=', 'pedido.id_pedido')
                ->join('producto', 'detallepedido.id_producto', '=', 'producto.id_producto')
                ->where('pedido.eliminado', 0)
                ->where('detallepedido.eliminado', 0)
                ->where('producto.eliminado', 0)
                ->where('pedido.estado_actual', 6)
                ->whereBetween('pedido.fecha_hora_registro', [$inicioMesAnterior, $finMesAnterior])
                ->select('producto.nombre', DB::raw('SUM(detallepedido.cantidad) as total_vendido'))
                ->groupBy('producto.id_producto', 'producto.nombre')
                ->orderByDesc('total_vendido')
                ->limit(5)
                ->get();
        } catch (\Exception $e) {
            Log::error('Error obteniendo top productos mes anterior: ' . $e->getMessage());
            return collect();
        }
    }

    public function generarPrediccion(Request $request)
    {
        ini_set('max_execution_time', 120);
        try {
            $script = base_path('scripts/prophet_forecast.py');
            $pythonCmd = 'python'; // o usa ruta completa: '/usr/bin/python3'

            if (!file_exists($script)) {
                Log::error('[PROPHET] ❌ Script no encontrado: ' . $script);
                return response()->json(['error' => 'Script de Prophet no encontrado'], 500);
            }

            // Verificar si Python está disponible
            $pythonCheck = exec("which $pythonCmd");
            Log::info('[PROPHET] Ruta de Python: ' . $pythonCheck);

            if (!$pythonCheck) {
                return response()->json(['error' => 'Python no está disponible en el sistema'], 500);
            }

            // Ejecutar el script
            $cmd = "\"$pythonCmd\" \"$script\"";
            Log::info('[PROPHET] Ejecutando comando: ' . $cmd);

            $output = [];
            $status = null;

            exec("$cmd 2>&1", $output, $status);

            Log::info('[PROPHET] Salida del script:', $output);
            Log::info('[PROPHET] Código de salida: ' . $status);

            if ($status !== 0) {
                return response()->json([
                    'error' => 'Error al ejecutar Prophet',
                    'details' => implode("\n", $output),
                ], 500);
            }

            // Verificar si se generó el archivo
            $forecastPath = storage_path('app/forecast.json');
            if (!file_exists($forecastPath)) {
                Log::error('[PROPHET] ❌ No se generó forecast.json');
                return response()->json(['error' => 'No se generó el archivo forecast.json'], 500);
            }

            Log::info('[PROPHET] ✓ Predicción generada correctamente');
            return response()->json(['message' => 'Predicción generada correctamente con Prophet']);

        } catch (\Exception $e) {
            Log::error('[PROPHET] ❌ Excepción: ' . $e->getMessage());
            return response()->json(['error' => 'Error interno al ejecutar Prophet'], 500);
        }
    }

    public function exportCSV()
    {
        $forecastPath = storage_path('app/forecast.json');

        if (!file_exists($forecastPath)) {
            return response()->json([
                'error' => 'No hay datos de predicción disponibles',
                'path' => $forecastPath
            ], 404);
        }

        try {
            $data = json_decode(file_get_contents($forecastPath), true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                return response()->json(['error' => 'Error al leer los datos de predicción'], 500);
            }

            $csv = "fecha,prediccion,real\n";

            foreach ($data['general'] ?? [] as $row) {
                $real = isset($row['real']) && $row['real'] !== null ? $row['real'] : '';
                $prediccion = isset($row['yhat']) ? round($row['yhat'], 2) : '';
                $csv .= "{$row['ds']},{$prediccion},{$real}\n";
            }

            return Response::make($csv, 200, [
                'Content-Type' => 'text/csv',
                'Content-Disposition' => 'attachment; filename="forecast_' . date('Y-m-d') . '.csv"'
            ]);
        } catch (\Exception $e) {
            Log::error('Error al exportar CSV: ' . $e->getMessage());
            return response()->json(['error' => 'Error al generar el archivo CSV'], 500);
        }
    }

    public function debug()
    {
        $forecastPath = storage_path('app/forecast.json');

        $info = [
            'forecast_path' => $forecastPath,
            'forecast_exists_file' => file_exists($forecastPath),
            'forecast_exists_storage' => Storage::exists('forecast.json'),
            'forecast_size' => file_exists($forecastPath) ? filesize($forecastPath) : 0,
            'pedidos_count' => DB::table('pedido')->where('eliminado', 0)->where('estado_actual', 6)->count(),
            'detalles_count' => DB::table('detallepedido')->where('eliminado', 0)->count(),
            'productos_count' => DB::table('producto')->where('eliminado', 0)->count(),
            'storage_path' => storage_path('app'),
            'script_path' => base_path('scripts/prophet_forecast.py'),
            'script_exists' => file_exists(base_path('scripts/prophet_forecast.py')),
            'python_available' => shell_exec('which python') ? 'python' : (shell_exec('which python') ? 'python' : 'No encontrado'),
            'storage_permissions' => is_writable(storage_path('app')) ? 'writable' : 'not writable',
            'directory_contents' => scandir(storage_path('app'))
        ];

        if (file_exists($forecastPath)) {
            try {
                $forecast = json_decode(file_get_contents($forecastPath), true);
                $info['forecast_preview'] = [
                    'general_count' => count($forecast['general'] ?? []),
                    'product_count' => count($forecast['por_producto'] ?? []),
                    'first_general' => isset($forecast['general'][0]) ? $forecast['general'][0] : null,
                    'json_valid' => json_last_error() === JSON_ERROR_NONE,
                    'json_error' => json_last_error_msg()
                ];
            } catch (\Exception $e) {
                $info['forecast_error'] = $e->getMessage();
            }
        }

        return response()->json($info);
    }
    public function getPrediccionesPorProducto(Request $request)
    {
        try {
            $idProducto = $request->get('id_producto');
            $diasFuturos = $request->get('dias', 30); // Por defecto 30 días
            $fechaDesde = now()->format('Y-m-d');
            $fechaHasta = now()->addDays($diasFuturos)->format('Y-m-d');

            $query = DB::table('prediccion as p')
                ->join('producto as pr', 'p.id_producto', '=', 'pr.id_producto')
                ->where('p.eliminado', 0)
                ->where('pr.eliminado', 0)
                ->whereBetween('p.fecha_predicha', [$fechaDesde, $fechaHasta])
                ->select(
                    'p.id_prediccion',
                    'p.id_producto',
                    'pr.nombre as producto',
                    'p.fecha_predicha',
                    'p.demanda_prevista',
                    'p.tipo_sugerencia',
                    'p.sugerencia_descripcion',
                    'p.fecha_generada',
                    'p.aceptado'
                )
                ->orderBy('p.fecha_predicha');

            if ($idProducto) {
                $query->where('p.id_producto', $idProducto);
            }

            $predicciones = $query->get();

            return response()->json([
                'predicciones' => $predicciones,
                'resumen' => [
                    'total_predicciones' => $predicciones->count(),
                    'demanda_total' => $predicciones->sum('demanda_prevista'),
                    'promedio_diario' => $predicciones->avg('demanda_prevista'),
                    'productos_unicos' => $predicciones->unique('id_producto')->count()
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('Error obteniendo predicciones: ' . $e->getMessage());
            return response()->json(['error' => 'Error obteniendo predicciones'], 500);
        }
    }

    /**
     * Obtener sugerencias por tipo
     */
    public function getSugerenciasPorTipo(Request $request)
    {
        try {
            $tipo = $request->get('tipo'); // 'stock_critico', 'incrementar_stock', etc.
            $fechaDesde = now()->format('Y-m-d');
            $fechaHasta = now()->addDays(30)->format('Y-m-d');

            $query = DB::table('prediccion as p')
                ->join('producto as pr', 'p.id_producto', '=', 'pr.id_producto')
                ->where('p.eliminado', 0)
                ->where('pr.eliminado', 0)
                ->where('p.aceptado', 0) // Solo no aceptadas
                ->whereBetween('p.fecha_predicha', [$fechaDesde, $fechaHasta])
                ->select(
                    'p.id_prediccion',
                    'p.id_producto',
                    'pr.nombre as producto',
                    'p.tipo_sugerencia',
                    'p.sugerencia_descripcion',
                    DB::raw('SUM(p.demanda_prevista) as demanda_total'),
                    DB::raw('AVG(p.demanda_prevista) as demanda_promedio'),
                    DB::raw('COUNT(*) as dias_prediccion')
                )
                ->groupBy('p.id_producto', 'pr.nombre', 'p.tipo_sugerencia', 'p.id_prediccion', 'p.sugerencia_descripcion');

            if ($tipo) {
                $query->where('p.tipo_sugerencia', $tipo);
            }

            $sugerencias = $query->get();

            return response()->json([
                'sugerencias' => $sugerencias,
                'tipos_disponibles' => [
                    'stock_critico' => 'Stock Crítico',
                    'incrementar_stock' => 'Incrementar Stock',
                    'mantener_stock' => 'Mantener Stock',
                    'reducir_stock' => 'Reducir Stock'
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('Error obteniendo sugerencias: ' . $e->getMessage());
            return response()->json(['error' => 'Error obteniendo sugerencias'], 500);
        }
    }

    /**
     * Aceptar/rechazar una sugerencia
     */
    public function accionSugerencia(Request $request)
    {
        try {
            $request->validate([
                'id_prediccion' => 'required|integer',
                'accion' => 'required|in:aceptar,rechazar',
                'id_usuario' => 'required|integer'
            ]);

            $aceptado = $request->accion === 'aceptar' ? 1 : 0;

            DB::table('prediccion')
                ->where('id_prediccion', $request->id_prediccion)
                ->update([
                    'aceptado' => $aceptado,
                    'id_usuario_accion' => $request->id_usuario
                ]);

            return response()->json([
                'message' => 'Sugerencia ' . ($aceptado ? 'aceptada' : 'rechazada') . ' correctamente'
            ]);
        } catch (\Exception $e) {
            Log::error('Error procesando sugerencia: ' . $e->getMessage());
            return response()->json(['error' => 'Error procesando sugerencia'], 500);
        }
    }

    /**
     * Obtener predicciones mensuales (para gráficos a largo plazo)
     */
    public function getPrediccionesMensuales(Request $request)
    {
        try {
            $mesesFuturos = $request->get('meses', 3);
            $fechaDesde = now()->format('Y-m-d');
            $fechaHasta = now()->addMonths($mesesFuturos)->format('Y-m-d');

            // Predicciones agrupadas por mes
            $prediccionesMensuales = DB::table('prediccion as p')
                ->join('producto as pr', 'p.id_producto', '=', 'pr.id_producto')
                ->where('p.eliminado', 0)
                ->where('pr.eliminado', 0)
                ->whereBetween('p.fecha_predicha', [$fechaDesde, $fechaHasta])
                ->select(
                    DB::raw('YEAR(p.fecha_predicha) as año'),
                    DB::raw('MONTH(p.fecha_predicha) as mes'),
                    DB::raw('MONTHNAME(p.fecha_predicha) as nombre_mes'),
                    DB::raw('SUM(p.demanda_prevista) as demanda_total'),
                    DB::raw('AVG(p.demanda_prevista) as demanda_promedio'),
                    DB::raw('COUNT(DISTINCT p.id_producto) as productos_activos')
                )
                ->groupBy('año', 'mes', 'nombre_mes')
                ->orderBy('año')
                ->orderBy('mes')
                ->get();

            // Top productos por mes
            $topProductosMensuales = DB::table('prediccion as p')
                ->join('producto as pr', 'p.id_producto', '=', 'pr.id_producto')
                ->where('p.eliminado', 0)
                ->where('pr.eliminado', 0)
                ->whereBetween('p.fecha_predicha', [$fechaDesde, $fechaHasta])
                ->select(
                    DB::raw('YEAR(p.fecha_predicha) as año'),
                    DB::raw('MONTH(p.fecha_predicha) as mes'),
                    'pr.nombre as producto',
                    DB::raw('SUM(p.demanda_prevista) as demanda_total')
                )
                ->groupBy('año', 'mes', 'p.id_producto', 'pr.nombre')
                ->orderBy('demanda_total', 'desc')
                ->get()
                ->groupBy(['año', 'mes'])
                ->map(function ($productosPorMes) {
                    return $productosPorMes->take(5); // Top 5 por mes
                });

            return response()->json([
                'predicciones_mensuales' => $prediccionesMensuales,
                'top_productos_mensuales' => $topProductosMensuales,
                'periodo' => [
                    'desde' => $fechaDesde,
                    'hasta' => $fechaHasta,
                    'meses' => $mesesFuturos
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('Error obteniendo predicciones mensuales: ' . $e->getMessage());
            return response()->json(['error' => 'Error obteniendo predicciones mensuales'], 500);
        }
    }

    /**
     * Dashboard con datos de la tabla prediccion
     */
    public function dashboardPredicciones()
    {
        try {
            $fechaHoy = now()->format('Y-m-d');
            $fechaProximaSemana = now()->addWeek()->format('Y-m-d');
            $fechaProximoMes = now()->addMonth()->format('Y-m-d');

            // Métricas principales
            $metricas = [
                // Predicciones próxima semana
                'predicciones_semana' => DB::table('prediccion')
                    ->where('eliminado', 0)
                    ->whereBetween('fecha_predicha', [$fechaHoy, $fechaProximaSemana])
                    ->sum('demanda_prevista'),

                // Productos con alertas críticas
                'alertas_criticas' => DB::table('prediccion')
                    ->where('eliminado', 0)
                    ->where('tipo_sugerencia', 'stock_critico')
                    ->where('aceptado', 0)
                    ->whereBetween('fecha_predicha', [$fechaHoy, $fechaProximoMes])
                    ->count(),

                // Sugerencias pendientes
                'sugerencias_pendientes' => DB::table('prediccion')
                    ->where('eliminado', 0)
                    ->where('aceptado', 0)
                    ->whereBetween('fecha_predicha', [$fechaHoy, $fechaProximoMes])
                    ->count(),

                // Producto con mayor demanda prevista
                'producto_mayor_demanda' => DB::table('prediccion as p')
                    ->join('producto as pr', 'p.id_producto', '=', 'pr.id_producto')
                    ->where('p.eliminado', 0)
                    ->where('pr.eliminado', 0)
                    ->whereBetween('p.fecha_predicha', [$fechaHoy, $fechaProximoMes])
                    ->select('pr.nombre', DB::raw('SUM(p.demanda_prevista) as demanda_total'))
                    ->groupBy('p.id_producto', 'pr.nombre')
                    ->orderBy('demanda_total', 'desc')
                    ->first()
            ];

            // Gráfico de demanda próximos 30 días
            $demandaDiaria = DB::table('prediccion as p')
                ->join('producto as pr', 'p.id_producto', '=', 'pr.id_producto')
                ->where('p.eliminado', 0)
                ->where('pr.eliminado', 0)
                ->whereBetween('p.fecha_predicha', [$fechaHoy, now()->addDays(30)->format('Y-m-d')])
                ->select(
                    'p.fecha_predicha',
                    DB::raw('SUM(p.demanda_prevista) as demanda_total')
                )
                ->groupBy('p.fecha_predicha')
                ->orderBy('p.fecha_predicha')
                ->get();

            // Sugerencias por tipo
            $sugerenciasPorTipo = DB::table('prediccion')
                ->where('eliminado', 0)
                ->where('aceptado', 0)
                ->whereBetween('fecha_predicha', [$fechaHoy, $fechaProximoMes])
                ->select(
                    'tipo_sugerencia',
                    DB::raw('COUNT(*) as cantidad')
                )
                ->groupBy('tipo_sugerencia')
                ->get();

            return Inertia::render('Admin/DashboardPredicciones', [
                'metricas' => $metricas,
                'demanda_diaria' => $demandaDiaria,
                'sugerencias_por_tipo' => $sugerenciasPorTipo
            ]);
        } catch (\Exception $e) {
            Log::error('Error en dashboard predicciones: ' . $e->getMessage());
            return Inertia::render('Admin/DashboardPredicciones', [
                'error' => 'Error cargando datos de predicciones'
            ]);
        }
    }
}
