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
    try {
        $script = base_path('scripts/prophet_forecast.py');
        $pythonCmd = 'python'; // Usar solo 'python' en Windows

        // Verificar existencia del script
        if (!file_exists($script)) {
            Log::error('Script no encontrado: ' . $script);
            return response()->json(['error' => 'Script no encontrado'], 500);
        }

        // Ejecutar comando SIMPLIFICADO (Windows)
        $cmd = escapeshellcmd("$pythonCmd $script");
        Log::info('Ejecutando comando: ' . $cmd);

        exec($cmd, $output, $status);

        if ($status !== 0) {
            Log::error('Error al ejecutar Prophet', ['output' => $output, 'status' => $status]);
            return response()->json([
                'error' => 'Error al ejecutar el script',
                'details' => implode("\n", $output),
            ], 500);
        }

        // Verificar archivo generado
        $forecastPath = storage_path('app/forecast.json');
        if (!file_exists($forecastPath)) {
            return response()->json(['error' => 'No se generó forecast.json'], 500);
        }

        return response()->json(['message' => 'Predicción generada correctamente']);

    } catch (\Exception $e) {
        Log::error('Excepción: ' . $e->getMessage());
        return response()->json(['error' => 'Error interno'], 500);
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
}
