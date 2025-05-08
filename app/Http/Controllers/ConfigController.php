<?php

namespace App\Http\Controllers;

use App\Models\Configuracion;
use Illuminate\Http\Request;
use App\Models\Estadopedido;

class ConfigController extends Controller
{
    public function index()
    {
        return inertia('config/Index', [
            'config' => [
                'tiempo_cancelacion_minutos' => (int) Configuracion::obtener('tiempo_cancelacion_minutos', 5),
                'tiempo_edicion_minutos' => (int) Configuracion::obtener('tiempo_edicion_minutos', 10),
                'estados_cancelables' => json_decode(Configuracion::obtener('estados_cancelables', '[]'), true),
                'estados_editables' => json_decode(Configuracion::obtener('estados_editables', '[]'), true),
                'todos_los_estados' => Estadopedido::pluck('nombre_estado')->toArray(),
            ]
        ]);
    }

    public function actualizarTiempoCancelacion(Request $request)
    {
        $request->validate([
            'minutos' => 'required|integer|min:0'
        ]);

        Configuracion::establecer('tiempo_cancelacion_minutos', $request->minutos);

        return response()->json(['success' => true]);
    }

    public function actualizarTiempoEdicion(Request $request)
    {
        $request->validate([
            'minutos' => 'required|integer|min:0'
        ]);

        Configuracion::establecer('tiempo_edicion_minutos', $request->minutos);

        return response()->json(['success' => true]);
    }

    public function actualizarEstados(Request $request)
    {
        $request->validate([
            'tipo' => 'required|in:editables,cancelables',
            'estados' => 'required|array',
            'estados.*' => 'string'
        ]);

        $clave = $request->tipo === 'editables' ? 'estados_editables' : 'estados_cancelables';

        Configuracion::establecer($clave, json_encode($request->estados));

        return response()->json(['success' => true]);
    }
}
