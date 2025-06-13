<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\DB;
use App\Models\Auditorium;
use App\Models\Usuario;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;
class AuditoriaController extends Controller
{
    public function index(Request $request)
    {
        $query = Auditorium::with('usuario')->where('eliminado', 0);

        if ($request->filled('usuario_id')) {
            $query->where('id_usuario', $request->usuario_id);
        }

        if ($request->filled('accion')) {
            $query->where('accion', 'like', '%' . $request->accion . '%');
        }

        if ($request->filled('fecha_inicio')) {
            $query->whereDate('fecha_hora', '>=', $request->fecha_inicio);
        }

        if ($request->filled('fecha_fin')) {
            $query->whereDate('fecha_hora', '<=', $request->fecha_fin);
        }

        $logs = $query->orderByDesc('fecha_hora')->paginate(50)->withQueryString();
        $usuarios = Usuario::where('eliminado', 0)->get(['id_usuario', 'nombre']);
        $acciones = DB::table('auditoria')
            ->where('eliminado', 0)
            ->distinct()
            ->pluck('accion');

        return Inertia::render('Audit/Index', [
            'logs' => $logs,
            'usuarios' => $usuarios,
            'acciones' => $acciones,
            'filtros' => $request->only(['usuario_id', 'accion', 'fecha_inicio', 'fecha_fin']),
        ]);
    }
    public function exportarExcel(Request $request): StreamedResponse
{
    $query = Auditorium::with('usuario')->where('eliminado', 0);

    if ($request->filled('usuario_id')) {
        $query->where('id_usuario', $request->usuario_id);
    }

    if ($request->filled('accion')) {
        $query->where('accion', 'like', '%' . $request->accion . '%');
    }

    if ($request->filled('fecha_inicio')) {
        $query->whereDate('fecha_hora', '>=', $request->fecha_inicio);
    }

    if ($request->filled('fecha_fin')) {
        $query->whereDate('fecha_hora', '<=', $request->fecha_fin);
    }

    $registros = $query->orderByDesc('fecha_hora')->get();

    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();

    $sheet->fromArray(['ID Usuario', 'Nombre Usuario', 'Acción', 'Descripción', 'Fecha y Hora'], null, 'A1');

    foreach ($registros as $i => $log) {
        $sheet->fromArray([
            $log->id_usuario,
            $log->usuario?->nombre ?? '',
            $log->accion,
            $log->descripcion,
            $log->fecha_hora,
        ], null, 'A' . ($i + 2));
    }

    $writer = new Xlsx($spreadsheet);

    return response()->streamDownload(function () use ($writer) {
        $writer->save('php://output');
    }, 'auditoria.xlsx');
}
}
