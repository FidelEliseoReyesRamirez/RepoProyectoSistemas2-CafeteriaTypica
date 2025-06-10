<?php
namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Response;

class DashboardController extends Controller
{
    public function index()
    {
        $forecast = json_decode(Storage::get('forecast.json'), true);

        $ventasSemana = DB::table('detallepedido')
            ->join('pedido', 'detallepedido.id_pedido', '=', 'pedido.id_pedido')
            ->whereBetween('pedido.fecha_hora_registro', [now()->startOfWeek(), now()])
            ->sum(DB::raw('detallepedido.cantidad * detallepedido.precio_unitario'));

        $clientes = DB::table('pedido')
            ->whereBetween('fecha_hora_registro', [now()->startOfWeek(), now()])
            ->count();

        $ticketPromedio = $clientes > 0 ? round($ventasSemana / $clientes, 2) : 0;

        $topProduct = DB::table('detallepedido')
            ->join('producto', 'detallepedido.id_producto', '=', 'producto.id_producto')
            ->select('producto.nombre', DB::raw('SUM(cantidad) as cantidad'))
            ->groupBy('producto.id_producto', 'producto.nombre')
            ->orderByDesc('cantidad')
            ->first();

        $ventasPorDia = DB::table('pedido')
            ->selectRaw('DATE(fecha_hora_registro) as dia, COUNT(*) as ventas')
            ->groupByRaw('DATE(fecha_hora_registro)')
            ->orderBy('dia')
            ->get();

        $franjaActiva = DB::table('pedido')
            ->selectRaw('HOUR(fecha_hora_registro) as hora, COUNT(*) as cantidad')
            ->groupByRaw('HOUR(fecha_hora_registro)')
            ->orderByDesc('cantidad')
            ->first();

        $comboSugerido = DB::table('detallepedido')
            ->join('pedido', 'detallepedido.id_pedido', '=', 'pedido.id_pedido')
            ->join('producto', 'detallepedido.id_producto', '=', 'producto.id_producto')
            ->select('producto.nombre', DB::raw('COUNT(*) as frecuencia'))
            ->whereBetween('pedido.fecha_hora_registro', [now()->subDays(30), now()])
            ->groupBy('producto.nombre')
            ->orderByDesc('frecuencia')
            ->limit(3)
            ->get();

        return Inertia::render('Admin/Dashboard', [
            'forecast' => $forecast['general'] ?? [],
            'porProducto' => $forecast['por_producto'] ?? [],
            'porCombo' => $forecast['por_combo'] ?? [],
            'metrics' => [
                'ventasSemana' => round($ventasSemana, 2),
                'clientesAtendidos' => $clientes,
                'ticketPromedio' => $ticketPromedio,
            ],
            'topProduct' => $topProduct ?? ['nombre' => 'N/A', 'cantidad' => 0],
            'ventasDiarias' => $ventasPorDia,
            'comboSugerido' => $comboSugerido,
            'franjaActiva' => $franjaActiva,
        ]);
    }

    public function generarPrediccion()
    {
        exec('python3 ' . base_path('scripts/prophet_forecast.py'), $output, $status);

        if ($status !== 0) {
            return response()->json(['error' => 'Error al ejecutar Prophet'], 500);
        }

        return response()->json(['message' => 'Predicción generada correctamente.']);
    }

    public function exportCSV()
    {
        $data = json_decode(Storage::get('forecast.json'), true);
        $csv = "fecha,yhat,real\n";
        foreach ($data['general'] ?? [] as $row) {
            $csv .= "{$row['ds']},{$row['yhat']},{$row['real']}\n";
        }
        return Response::make($csv, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="forecast.csv"'
        ]);
    }
}
