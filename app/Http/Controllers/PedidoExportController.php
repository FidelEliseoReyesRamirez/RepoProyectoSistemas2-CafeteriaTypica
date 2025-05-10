<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PedidoExportController extends Controller
{
    public function export(Request $request)
    {
        // Puedes aplicar filtros si necesitas (por ejemplo por fecha, estado, etc.)
        $pedidos = \App\Models\Pedido::with([
            'estadopedido',
            'usuario', // Cambié de 'usuario_mesero' a 'usuario'
            'detallepedidos.producto'
        ])->get();

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // Cabeceras
        $sheet->fromArray([
            'ID', 'Fecha', 'Mesero', 'Estado', 'Producto', 'Cantidad', 'Comentario', 'Precio Unitario', 'Subtotal'
        ], null, 'A1');

        $row = 2;
        foreach ($pedidos as $pedido) {
            foreach ($pedido->detallepedidos as $detalle) {
                $sheet->fromArray([
                    $pedido->id_pedido,
                    $pedido->fecha_hora_registro,
                    $pedido->usuario->nombre ?? 'Sin asignar', // Cambié de 'usuario_mesero' a 'usuario'
                    $pedido->estadopedido->nombre_estado,
                    $detalle->producto->nombre,
                    $detalle->cantidad,
                    $detalle->comentario,
                    $detalle->producto->precio,
                    $detalle->producto->precio * $detalle->cantidad
                ], null, "A$row");
                $row++;
            }
        }

        $writer = new Xlsx($spreadsheet);

        // Descargar como archivo Excel
        return new StreamedResponse(function () use ($writer) {
            $writer->save('php://output');
        }, 200, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment;filename="pedidos.xlsx"',
            'Cache-Control' => 'max-age=0',
        ]);
    }
}
