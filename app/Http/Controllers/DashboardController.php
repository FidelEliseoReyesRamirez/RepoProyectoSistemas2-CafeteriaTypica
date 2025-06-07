<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class DashboardController extends Controller
{
    public function index()
    {
        // Leer forecast desde JSON generado por Prophet
        $forecast = json_decode(Storage::disk('local')->get('forecast.json'), true);

        // Consultas reales desde MySQL
        $ventasSemana = DB::table('detallepedido')
            ->join('pedido', 'detallepedido.id_pedido', '=', 'pedido.id_pedido')
            ->whereBetween('pedido.fecha_hora_registro', [now()->startOfWeek(), now()])
            ->sum(DB::raw('detallepedido.cantidad * detallepedido.precio_unitario'));

        $clientes = DB::table('pedido')
            ->whereBetween('fecha_hora_registro', [now()->startOfWeek(), now()])
            ->count();

        $ticketPromedio = $clientes > 0 ? round($ventasSemana / $clientes, 2) : 0;

        $topProduct = DB::table('detallepedido')
            ->select('producto.nombre', DB::raw('SUM(cantidad) as cantidad'))
            ->join('producto', 'detallepedido.id_producto', '=', 'producto.id_producto')
            ->groupBy('producto.id_producto', 'producto.nombre')
            ->orderByDesc('cantidad')
            ->first();

        $ventasPorDia = DB::table('pedido')
            ->selectRaw('DAYNAME(fecha_hora_registro) as dia, COUNT(*) as ventas')
            ->whereBetween('fecha_hora_registro', [now()->subDays(7), now()])
            ->groupByRaw('DAYNAME(fecha_hora_registro)')
            ->get();

        $comboSugerido = [
            ['producto' => 'Café Americano', 'frecuencia' => 120],
            ['producto' => 'Empanada de Queso', 'frecuencia' => 110],
            ['producto' => 'Jugo de Naranja', 'frecuencia' => 95],
        ];

        return Inertia::render('Admin/Dashboard', [
            'forecast' => $forecast,
            'metrics' => [
                'ventasSemana' => round($ventasSemana, 2),
                'clientesAtendidos' => $clientes,
                'ticketPromedio' => $ticketPromedio,
            ],
            'topProduct' => $topProduct ?? ['nombre' => 'N/A', 'cantidad' => 0],
            'ventasDiarias' => $ventasPorDia,
            'comboSugerido' => $comboSugerido,
        ]);
    }

    public function generarPrediccion()
    {
        // Ejecutar script de Python
        exec('python3 '.base_path('scripts/prophet_forecast.py'), $output, $status);

        if ($status !== 0) {
            return response()->json(['error' => 'Error al ejecutar Prophet'], 500);
        }

        return response()->json(['message' => 'Predicción generada']);
    }
}
