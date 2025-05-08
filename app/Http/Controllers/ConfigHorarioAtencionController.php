<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ConfigHorarioAtencion;

class ConfigHorarioAtencionController extends Controller
{
    public function index()
    {
        $horarios = ConfigHorarioAtencion::all();
        return response()->json($horarios);
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'horarios' => 'required|array',
            'horarios.*.dia' => 'required|string',
            'horarios.*.hora_inicio' => 'required|date_format:H:i:s',
            'horarios.*.hora_fin' => 'required|date_format:H:i:s',
        ]);

        foreach ($data['horarios'] as $horario) {
            ConfigHorarioAtencion::updateOrCreate(
                ['dia' => $horario['dia']],
                ['hora_inicio' => $horario['hora_inicio'], 'hora_fin' => $horario['hora_fin']]
            );
        }

        return response()->json(['message' => 'Horarios actualizados correctamente']);
    }
}
