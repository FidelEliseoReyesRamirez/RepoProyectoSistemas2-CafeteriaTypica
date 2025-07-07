<?php

namespace App\Http\Controllers;

use App\Models\ConfigEstadoPedido;
use Illuminate\Http\Request;
use Inertia\Inertia;
class ConfiguracionController extends Controller
{
    public function index()
    {
        $config = ConfigEstadoPedido::all();

        return Inertia::render('config/index', [
            'config' => [
                'tiempo_cancelacion_minutos' => $config->first()?->tiempo_cancelacion_minutos ?? 5,
                'tiempo_edicion_minutos' => $config->first()?->tiempo_edicion_minutos ?? 10,
                'estados' => $config->map(fn($item) => [
                    'estado' => $item->estado,
                    'puede_cancelar' => (bool) $item->puede_cancelar,
                    'puede_editar' => (bool) $item->puede_editar,
                ]),
            ]
        ]);
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'tiempo_cancelacion_minutos' => 'required|integer|min:0',
            'tiempo_edicion_minutos' => 'required|integer|min:0',
            'estados' => 'required|array',
            'estados.*.estado' => 'required|string',
            'estados.*.puede_cancelar' => 'boolean',
            'estados.*.puede_editar' => 'boolean',
        ]);

        foreach ($data['estados'] as $estado) {
            ConfigEstadoPedido::where('estado', $estado['estado'])->update([
                'tiempo_cancelacion_minutos' => $data['tiempo_cancelacion_minutos'],
                'tiempo_edicion_minutos' => $data['tiempo_edicion_minutos'],
                'puede_cancelar' => $estado['puede_cancelar'],
                'puede_editar' => $estado['puede_editar'],
            ]);
        }

        return back()->with('success', 'Configuración guardada');

    }
}
