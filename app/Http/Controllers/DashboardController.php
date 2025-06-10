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
        // Debug: Verificar si el archivo existe
        $forecastExists = Storage::exists('forecast.json');
        Log::info('Forecast file exists: ' . ($forecastExists ? 'Yes' : 'No'));

        $forecast = [];
        if ($forecastExists) {
            $forecastContent = Storage::get('forecast.json');
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
        } else {
            Log::warning('Forecast file does not exist');
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
        $script = base_path('scripts/prophet_forecast.py');

        // Verificar que el script existe
        if (!file_exists($script)) {
            Log::error('Script de Prophet no encontrado: ' . $script);
            return response()->json(['error' => 'Script de Prophet no encontrado'], 500);
        }

        $cmd = "python3 " . escapeshellarg($script) . " 2>&1";
        Log::info('Ejecutando comando: ' . $cmd);

        exec($cmd, $output, $status);

        Log::info('Salida del script Prophet:', $output);
        Log::info('Status del script: ' . $status);

        if ($status !== 0) {
            return response()->json(['error' => 'Error al ejecutar Prophet', 'details' => $output], 500);
        }

        return response()->json(['message' => 'Predicción generada correctamente.', 'output' => $output]);
    }

    public function exportCSV()
    {
        if (!Storage::exists('forecast.json')) {
            return response()->json(['error' => 'No hay datos de predicción disponibles'], 404);
        }

        $data = json_decode(Storage::get('forecast.json'), true);
        $csv = "fecha,yhat,real\n";

        foreach ($data['general'] ?? [] as $row) {
            $real = $row['real'] ?? '';
            $csv .= "{$row['ds']},{$row['yhat']},{$real}\n";
        }

        return Response::make($csv, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="forecast.csv"'
        ]);
    }

    // Método de debug para verificar datos
    public function debug()
    {
        $info = [
            'forecast_exists' => Storage::exists('forecast.json'),
            'forecast_size' => Storage::exists('forecast.json') ? Storage::size('forecast.json') : 0,
            'pedidos_count' => DB::table('pedido')->where('eliminado', 0)->count(),
            'detalles_count' => DB::table('detallepedido')->where('eliminado', 0)->count(),
            'productos_count' => DB::table('producto')->where('eliminado', 0)->count(),
            'storage_path' => storage_path('app/forecast.json'),
            'script_path' => base_path('scripts/prophet_forecast.py'),
            'script_exists' => file_exists(base_path('scripts/prophet_forecast.py'))
        ];

        if (Storage::exists('forecast.json')) {
            $forecast = json_decode(Storage::get('forecast.json'), true);
            $info['forecast_preview'] = [
                'general_count' => count($forecast['general'] ?? []),
                'product_count' => count($forecast['por_producto'] ?? []),
                'first_general' => isset($forecast['general'][0]) ? $forecast['general'][0] : null
            ];
        }

        return response()->json($info);
    }
}
