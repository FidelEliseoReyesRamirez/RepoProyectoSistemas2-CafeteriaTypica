<?php

namespace App\Http\Controllers;

use Barryvdh\DomPDF\Facade\Pdf;
use App\Models\Pedido;

class PedidoControllerAdminPDF extends Controller
{
    public function generarPDF($id)
    {
        // Traer el pedido con más detalles, incluyendo la relación 'categorium'
        $pedido = Pedido::with([
            'detallepedidos.producto.categorium', // Usamos 'categorium' aquí
            'estadopedido',
            'usuario'
        ])->find($id);

        if (!$pedido) {
            return response()->json(['error' => 'Pedido no encontrado'], 404);
        }

        // Establecer el tamaño del papel para el PDF
        $pdf = Pdf::loadView('pdf.pedido_admin', compact('pedido'))
            ->setPaper('A4', 'portrait'); // Tamaño A4, orientación vertical

        return $pdf->stream('comanda_admin_pedido_' . $pedido->id_pedido . '.pdf');
    }
}
