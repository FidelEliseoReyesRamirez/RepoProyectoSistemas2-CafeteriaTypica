<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;

class PedidoExportController extends Controller
{
    public function export(Request $request)
    {
        $columnas = $request->input('columnas', []);
        $inicio = $request->input('fecha_inicio');
        $fin = $request->input('fecha_fin');

        $pedidos = \App\Models\Pedido::with(['usuario', 'detallepedidos.producto', 'pago'])
            ->when($inicio && $fin, fn($q) => $q->whereBetween('fecha_hora_registro', [$inicio, $fin]))
            ->when($inicio && !$fin, fn($q) => $q->whereDate('fecha_hora_registro', $inicio))
            ->whereHas('pago', fn($q) => $q->where('eliminado', false))

            ->get();

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        $encabezado = ['ID', 'Fecha', 'Mesero', 'Estado', 'Producto', 'Cantidad', 'Comentario', 'Precio Unitario', 'Subtotal'];
        $columnasValidas = array_values(array_filter($encabezado, fn($col) => in_array($col, $columnas)));
        $sheet->fromArray($columnasValidas, null, 'A1');

        $sheet->getStyle('A1:' . Coordinate::stringFromColumnIndex(count($columnasValidas)) . '1')->applyFromArray([
            'font' => ['bold' => true],
            'fill' => ['fillType' => 'solid', 'startColor' => ['rgb' => 'e6d3ba']],
            'borders' => ['allBorders' => ['borderStyle' => 'thin']],
        ]);

        $row = 2;
        foreach ($pedidos as $pedido) {
            foreach ($pedido->detallepedidos as $detalle) {
                $linea = [
                    'ID' => $pedido->id_pedido,
                    'Fecha' => $pedido->fecha_hora_registro,
                    'Mesero' => $pedido->usuario->nombre ?? 'Sin asignar',
                    'Estado' => $pedido->estadopedido->nombre_estado ?? '',
                    'Producto' => $detalle->producto->nombre ?? '',
                    'Cantidad' => $detalle->cantidad,
                    'Comentario' => $detalle->comentario,
                    'Precio Unitario' => $detalle->precio_unitario,
                    'Subtotal' => $detalle->precio_unitario * $detalle->cantidad,
                ];
                $datos = [];
                foreach ($columnasValidas as $col) {
                    $datos[] = $linea[$col] ?? '';
                }
                $sheet->fromArray($datos, null, "A{$row}");
                $row++;
            }
        }

        $sheet->getStyle("A2:" . Coordinate::stringFromColumnIndex(count($columnasValidas)) . ($row - 1))
            ->applyFromArray(['borders' => ['allBorders' => ['borderStyle' => 'thin']]]);

        $sheet->setCellValue("A{$row}", 'TOTAL');
        $ultimaCol = Coordinate::stringFromColumnIndex(count($columnasValidas));
        $sheet->setCellValue("{$ultimaCol}{$row}", "=SUM({$ultimaCol}2:{$ultimaCol}" . ($row - 1) . ")");
        $sheet->getStyle("A{$row}:{$ultimaCol}{$row}")
            ->applyFromArray([
                'font' => ['bold' => true],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'f5ebe0']],
                'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
            ]);

        foreach (range(0, count($columnasValidas) - 1) as $i) {
            $sheet->getColumnDimensionByColumn($i + 1)->setAutoSize(true);
        }

        $writer = new Xlsx($spreadsheet);

        return new StreamedResponse(function () use ($writer) {
            $writer->save('php://output');
        }, 200, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment;filename="pedidos.xlsx"',
            'Cache-Control' => 'max-age=0',
        ]);
    }
}
