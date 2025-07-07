<?php

namespace App\Http\Controllers;

use Barryvdh\DomPDF\Facade\Pdf;
use App\Models\Pedido;

class PedidoControllerPDF extends Controller
{
    public function generarPDF($id)
    {
        $pedido = Pedido::with(['detallepedidos.producto', 'estadopedido'])->find($id);

        if (!$pedido) {
            return response()->json(['error' => 'Pedido no encontrado'], 404);
        }

        // Establecer un ancho fijo de 4 pulgadas (288 puntos) y un alto mínimo (por ejemplo, 6 pulgadas = 432 puntos)
        // El alto de 432 puntos es solo un ejemplo, puedes ajustarlo según el contenido
        $pdf = Pdf::loadView('pdf.pedido', compact('pedido'))
            ->setPaper([0, 0, 288, 432], 'portrait'); // 4 pulgadas de ancho (288 puntos) y 6 pulgadas de alto (432 puntos)

        return $pdf->stream('comanda_pedido_' . $pedido->id_pedido . '.pdf');
    }
}
