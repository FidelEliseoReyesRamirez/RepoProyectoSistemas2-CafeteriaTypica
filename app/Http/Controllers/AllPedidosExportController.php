<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use App\Models\Pedido;  // Asegúrate de que la relación Pedido esté bien definida
use Illuminate\Support\Facades\Log;  
class AllPedidosExportController extends Controller
{
    public function export(Request $request)
{
    // Establece las columnas predeterminadas si no se envían desde el frontend
    $columnas = $request->input('columnas', ['ID', 'Fecha', 'Mesero']); // Ejemplo de columnas predeterminadas
    
    $inicio = $request->input('fecha_inicio');
    $fin = $request->input('fecha_fin');

    // Registra en el log las columnas y fechas seleccionadas
    Log::info('Columnas seleccionadas: ', $columnas);
    Log::info('Fechas de filtro: Inicio - ' . $inicio . ', Fin - ' . $fin);

    // Filtra los pedidos
    $pedidos = Pedido::with([
        'usuario',
        'detallepedidos.producto',
        'pago' => fn($query) => $query->where('eliminado', false)
    ])
    ->when($inicio && $fin, fn($q) => $q->whereBetween('fecha_hora_registro', [$inicio, $fin]))
    ->when($inicio && !$fin, fn($q) => $q->whereDate('fecha_hora_registro', $inicio))
    ->get();

    // Verifica si los pedidos se están obteniendo correctamente
    Log::info('Pedidos obtenidos: ', $pedidos->toArray());

    // Crea una nueva instancia de PhpSpreadsheet
    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();

    // Establece el encabezado de las columnas
    $encabezado = ['ID', 'Fecha', 'Mesero', 'Estado', 'Producto', 'Cantidad', 'Comentario', 'Precio Unitario', 'Subtotal'];
    // Filtra las columnas de acuerdo con las seleccionadas
    $columnasValidas = array_values(array_filter($encabezado, fn($col) => in_array($col, $columnas)));
    $sheet->fromArray($columnasValidas, null, 'A1');

    // Aplica estilos al encabezado
    $sheet->getStyle('A1:' . Coordinate::stringFromColumnIndex(count($columnasValidas)) . '1')->applyFromArray([
        'font' => ['bold' => true],
        'fill' => ['fillType' => 'solid', 'startColor' => ['rgb' => 'e6d3ba']],
        'borders' => ['allBorders' => ['borderStyle' => 'thin']],
    ]);

    // Llena las filas con los datos de los pedidos
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

    // Aplica estilos a las filas de datos
    $sheet->getStyle("A2:" . Coordinate::stringFromColumnIndex(count($columnasValidas)) . ($row - 1))
        ->applyFromArray(['borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]]]);

    // Calcula el total
    $sheet->setCellValue("A{$row}", 'TOTAL');
    $ultimaCol = Coordinate::stringFromColumnIndex(count($columnasValidas));
    $sheet->setCellValue("{$ultimaCol}{$row}", "=SUM({$ultimaCol}2:{$ultimaCol}" . ($row - 1) . ")");
    $sheet->getStyle("A{$row}:{$ultimaCol}{$row}")
        ->applyFromArray([
            'font' => ['bold' => true],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'f5ebe0']],
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
        ]);

    // Ajusta el tamaño de las columnas
    foreach (range(0, count($columnasValidas) - 1) as $i) {
        $sheet->getColumnDimensionByColumn($i + 1)->setAutoSize(true);
    }

    // Crea el escritor de Excel
    $writer = new Xlsx($spreadsheet);

    // Retorna el archivo Excel como respuesta para su descarga
    return new StreamedResponse(function () use ($writer) {
        $writer->save('php://output');
    }, 200, [
        'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'Content-Disposition' => 'attachment;filename="pedidos.xlsx"',
        'Cache-Control' => 'max-age=0',
    ]);
}
}
