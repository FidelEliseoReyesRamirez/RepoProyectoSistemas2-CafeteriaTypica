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

        $pdf = Pdf::loadView('pdf.pedido', compact('pedido'));

        // Configuramos los encabezados para que el navegador reconozca el archivo como PDF y lo muestre en una nueva pestaña
        return response($pdf->output())
            ->header('Content-Type', 'application/pdf')
            ->header('Content-Disposition', 'inline; filename="comanda_pedido_' . $pedido->id_pedido . '.pdf"');
    }
}
