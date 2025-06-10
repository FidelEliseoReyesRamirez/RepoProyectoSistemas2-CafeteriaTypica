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
        // Intentar múltiples formas de verificar el archivo
        $forecastPath = storage_path('app/forecast.json');
        $forecastExists = file_exists($forecastPath);

        // Log adicional para debug
        Log::info('Verificando archivo forecast:', [
            'storage_path' => $forecastPath,
            'file_exists' => $forecastExists,
            'storage_facade_exists' => Storage::exists('forecast.json'),
            'storage_disk' => Storage::getDefaultDriver()
        ]);

        $forecast = [];
        if ($forecastExists) {
            try {
                // Usar file_get_contents directamente si Storage no funciona
                $forecastContent = file_get_contents($forecastPath);
                Log::info('Forecast content length: ' . strlen($forecastContent));

                $forecast = json_decode($forecastContent, true);
                if (json_last_error() !== JSON_ERROR_NONE) {
                    Log::error('JSON decode error: ' . json_last_error_msg());
                    $forecast = ['general' => [], 'por_producto' => []];
                } else {
                    Log::info('Forecast decoded successfully');
                    Log::info('General forecast count: ' . count($forecast['general'] ?? []));
                    Log::info('Product forecast count: ' . count($forecast['por_producto'] ?? []));
                }
            } catch (\Exception $e) {
                Log::error('Error reading forecast file: ' . $e->getMessage());
                $forecast = ['general' => [], 'por_producto' => []];
            }
        } else {
            Log::warning('Forecast file does not exist at: ' . $forecastPath);
            $forecast = ['general' => [], 'por_producto' => []];
        }

        // Métricas de la semana actual
        $ventasSemana = DB::table('detallepedido')
            ->join('pedido', 'detallepedido.id_pedido', '=', 'pedido.id_pedido')
            ->where('pedido.eliminado', 0)
            ->where('detallepedido.eliminado', 0)
            ->whereBetween('pedido.fecha_hora_registro', [now()->startOfWeek(), now()])
            ->sum(DB::raw('detallepedido.cantidad * detallepedido.precio_unitario'));

        $clientes = DB::table('pedido')
            ->where('eliminado', 0)
            ->whereBetween('fecha_hora_registro', [now()->startOfWeek(), now()])
            ->count();

        $ticketPromedio = $clientes > 0 ? round($ventasSemana / $clientes, 2) : 0;

        // Producto más vendido (en general, no solo esta semana)
        $topProduct = DB::table('detallepedido')
            ->join('producto', 'detallepedido.id_producto', '=', 'producto.id_producto')
            ->join('pedido', 'detallepedido.id_pedido', '=', 'pedido.id_pedido')
            ->where('pedido.eliminado', 0)
            ->where('detallepedido.eliminado', 0)
            ->where('producto.eliminado', 0)
            ->select('producto.nombre', DB::raw('SUM(detallepedido.cantidad) as cantidad'))
            ->groupBy('producto.id_producto', 'producto.nombre')
            ->orderByDesc('cantidad')
            ->first();

        // Ventas por día (últimos 90 días)
        $ventasPorDia = DB::table('pedido')
            ->where('eliminado', 0)
            ->selectRaw('DATE(fecha_hora_registro) as dia, COUNT(*) as ventas')
            ->whereBetween('fecha_hora_registro', [now()->subDays(90), now()])
            ->groupByRaw('DATE(fecha_hora_registro)')
            ->orderBy('dia')
            ->get();

        // Franja horaria más activa
        $franjaActiva = DB::table('pedido')
            ->where('eliminado', 0)
            ->selectRaw('HOUR(fecha_hora_registro) as hora, COUNT(*) as cantidad')
            ->groupByRaw('HOUR(fecha_hora_registro)')
            ->orderByDesc('cantidad')
            ->first();

        // Top productos del último mes
        $topProductos = DB::table('detallepedido')
            ->join('pedido', 'detallepedido.id_pedido', '=', 'pedido.id_pedido')
            ->join('producto', 'detallepedido.id_producto', '=', 'producto.id_producto')
            ->where('pedido.eliminado', 0)
            ->where('detallepedido.eliminado', 0)
            ->where('producto.eliminado', 0)
            ->select('producto.nombre', DB::raw('SUM(detallepedido.cantidad) as total_vendido'))
            ->whereBetween('pedido.fecha_hora_registro', [now()->subDays(30), now()])
            ->groupBy('producto.id_producto', 'producto.nombre')
            ->orderByDesc('total_vendido')
            ->limit(5)
            ->get();

        // Debug: Log de las métricas obtenidas
        Log::info('Dashboard metrics:', [
            'ventasSemana' => $ventasSemana,
            'clientes' => $clientes,
            'ticketPromedio' => $ticketPromedio,
            'topProduct' => $topProduct,
            'ventasPorDia_count' => $ventasPorDia->count(),
            'topProductos_count' => $topProductos->count(),
            'franjaActiva' => $franjaActiva
        ]);

        // Verificar si tenemos datos en las tablas
        $totalPedidos = DB::table('pedido')->where('eliminado', 0)->count();
        $totalDetalles = DB::table('detallepedido')->where('eliminado', 0)->count();
        Log::info("Total pedidos: $totalPedidos, Total detalles: $totalDetalles");

        return Inertia::render('Admin/Dashboard', [
            'forecast'       => $forecast['general'] ?? [],
            'porProducto'    => $forecast['por_producto'] ?? [],
            'metrics'        => [
                'ventasSemana'       => round($ventasSemana, 2),
                'clientesAtendidos'  => $clientes,
                'ticketPromedio'     => $ticketPromedio,
            ],
            'topProduct'     => $topProduct ?? ['nombre' => 'N/A', 'cantidad' => 0],
            'ventasDiarias'  => $ventasPorDia,
            'topProductos'   => $topProductos,
            'franjaActiva'   => $franjaActiva,
            // Debug info para el frontend
            'debug' => [
                'forecastExists' => $forecastExists,
                'totalPedidos' => $totalPedidos,
                'totalDetalles' => $totalDetalles,
                'forecastGeneralCount' => count($forecast['general'] ?? []),
                'forecastProductCount' => count($forecast['por_producto'] ?? [])
            ]
        ]);
    }

    public function generarPrediccion(Request $request)
    {
        try {
            $script = base_path('scripts/prophet_forecast.py');

            // Verificar que el script existe
            if (!file_exists($script)) {
                Log::error('Script de Prophet no encontrado: ' . $script);
                return response()->json(['error' => 'Script de Prophet no encontrado'], 500);
            }

            // Verificar si Python está disponible
            $pythonCommands = ['python3', 'python'];
            $pythonCmd = null;

            foreach ($pythonCommands as $cmd) {
                exec("which $cmd", $output, $status);
                if ($status === 0) {
                    $pythonCmd = $cmd;
                    break;
                }
            }

            if (!$pythonCmd) {
                Log::error('Python no encontrado en el sistema');
                return response()->json(['error' => 'Python no está instalado o no está en el PATH'], 500);
            }

            // Ejecutar el script Python
            $cmd = "$pythonCmd " . escapeshellarg($script) . " 2>&1";
            Log::info('Ejecutando comando: ' . $cmd);

            exec($cmd, $output, $status);

            Log::info('Salida del script Prophet:', $output);
            Log::info('Status del script: ' . $status);

            if ($status !== 0) {
                return response()->json([
                    'error' => 'Error al ejecutar Prophet',
                    'details' => $output,
                    'status' => $status,
                    'command' => $cmd
                ], 500);
            }

            // Verificar con ambos métodos
            $forecastPath = storage_path('app/forecast.json');
            $fileExists = file_exists($forecastPath);
            $storageExists = Storage::exists('forecast.json');

            if (!$fileExists) {
                return response()->json([
                    'error' => 'El script se ejecutó pero no se generó el archivo forecast.json',
                    'output' => $output,
                    'path_checked' => $forecastPath,
                    'file_exists' => $fileExists,
                    'storage_exists' => $storageExists
                ], 500);
            }

            return response()->json([
                'message' => 'Predicción generada correctamente.',
                'output' => $output,
                'file_exists' => $fileExists,
                'storage_exists' => $storageExists
            ]);

        } catch (\Exception $e) {
            Log::error('Excepción al generar predicción: ' . $e->getMessage());
            return response()->json([
                'error' => 'Error interno del servidor',
                'message' => $e->getMessage()
            ], 500);
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

    // Método de debug para verificar datos
    public function debug()
    {
        $forecastPath = storage_path('app/forecast.json');

        $info = [
            'forecast_path' => $forecastPath,
            'forecast_exists_file' => file_exists($forecastPath),
            'forecast_exists_storage' => Storage::exists('forecast.json'),
            'forecast_size' => file_exists($forecastPath) ? filesize($forecastPath) : 0,
            'pedidos_count' => DB::table('pedido')->where('eliminado', 0)->count(),
            'detalles_count' => DB::table('detallepedido')->where('eliminado', 0)->count(),
            'productos_count' => DB::table('producto')->where('eliminado', 0)->count(),
            'storage_path' => storage_path('app'),
            'script_path' => base_path('scripts/prophet_forecast.py'),
            'script_exists' => file_exists(base_path('scripts/prophet_forecast.py')),
            'python_available' => shell_exec('which python3') ? 'python3' : (shell_exec('which python') ? 'python' : 'No encontrado'),
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
