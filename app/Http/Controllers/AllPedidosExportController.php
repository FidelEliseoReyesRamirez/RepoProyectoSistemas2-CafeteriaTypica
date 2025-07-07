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
    // Establecer los filtros de acuerdo con los parámetros
    $pedidosQuery = Pedido::with([
        'usuario',
        'detallepedidos.producto',
        'pago' => fn($query) => $query->where('eliminado', false)
    ]);

    // Aplicar los filtros de la solicitud
    if ($estado = $request->input('estado')) {
        $pedidosQuery->whereHas('estadopedido', function ($query) use ($estado) {
            $query->where('nombre_estado', $estado);
        });
    }

    if ($numero = $request->input('numero')) {
        $pedidosQuery->where('id_pedido', 'like', "%$numero%");
    }

    if ($inicio = $request->input('fecha_inicio')) {
        $pedidosQuery->whereDate('fecha_hora_registro', '>=', $inicio);
    }

    if ($fin = $request->input('fecha_fin')) {
        $pedidosQuery->whereDate('fecha_hora_registro', '<=', $fin);
    }

    // Aplicar el filtro de tiempo
    if ($tiempo = $request->input('tiempo')) {
        $ahora = now();
        switch ($tiempo) {
            case 'ultima_hora':
                $pedidosQuery->where('fecha_hora_registro', '>=', $ahora->subHour());
                break;
            case 'ultimas_2':
                $pedidosQuery->where('fecha_hora_registro', '>=', $ahora->subHours(2));
                break;
            case 'hoy':
                $pedidosQuery->whereDate('fecha_hora_registro', $ahora->toDateString());
                break;
            case 'ultimas_24':
                $pedidosQuery->where('fecha_hora_registro', '>=', $ahora->subDay());
                break;
            case 'ultimos_2_dias':
                $pedidosQuery->where('fecha_hora_registro', '>=', $ahora->subDays(2));
                break;
            case 'ultima_semana':
                $pedidosQuery->where('fecha_hora_registro', '>=', $ahora->subWeek());
                break;
            case 'este_mes':
                $pedidosQuery->whereMonth('fecha_hora_registro', $ahora->month)
                             ->whereYear('fecha_hora_registro', $ahora->year);
                break;
            case 'rango_fechas':
                if ($inicio && $fin) {
                    $pedidosQuery->whereBetween('fecha_hora_registro', [$inicio, $fin]);
                }
                break;
            default:
                break;
        }
    }

    // Aplicar filtro de mesero
    if ($mesero = $request->input('mesero')) {
        $pedidosQuery->whereHas('usuario', function ($query) use ($mesero) {
            $query->where('nombre', 'like', "%$mesero%");
        });
    }

    // Obtener los pedidos filtrados
    $pedidos = $pedidosQuery->get();

    // Crear el archivo Excel
    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();

    // Encabezado de las columnas
    $encabezado = ['ID', 'Fecha', 'Mesero', 'Estado', 'Producto', 'Cantidad', 'Comentario', 'Precio Unitario', 'Subtotal'];
    $sheet->fromArray($encabezado, null, 'A1');

    // Llenar las filas con los datos de los pedidos
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
            $sheet->fromArray($linea, null, "A{$row}");
            $row++;
        }
    }

    // Añadir fila de total (suma) al final
    $sheet->setCellValue("A{$row}", 'TOTAL');
    $ultimaColumna = Coordinate::stringFromColumnIndex(count($encabezado));
    $sheet->setCellValue("{$ultimaColumna}{$row}", "=SUM({$ultimaColumna}2:{$ultimaColumna}" . ($row - 1) . ")");

    // Estilo para la fila de total
    $sheet->getStyle("A{$row}:{$ultimaColumna}{$row}")->applyFromArray([
        'font' => ['bold' => true],
        'fill' => ['fillType' => 'solid', 'startColor' => ['rgb' => 'f5ebe0']],
        'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
    ]);

    // Ajuste de las columnas para el formato de Excel
    foreach (range(0, count($encabezado) - 1) as $i) {
        // Limitar el ancho de la columna de comentarios a un máximo de 30 caracteres
        if ($i == 6) {  // Columna de 'Comentario' (índice 6)
            $sheet->getColumnDimensionByColumn($i + 1)->setWidth(30);
        } else {
            $sheet->getColumnDimensionByColumn($i + 1)->setAutoSize(true);
        }
    }

    // Estilo para las filas de datos
    $sheet->getStyle("A2:" . Coordinate::stringFromColumnIndex(count($encabezado)) . ($row - 1))
        ->applyFromArray(['borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]]]);

    // Crear el escritor de Excel
    $writer = new Xlsx($spreadsheet);

    // Enviar el archivo Excel como respuesta para la descarga
    return new StreamedResponse(function () use ($writer) {
        $writer->save('php://output');
    }, 200, [
        'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'Content-Disposition' => 'attachment;filename="pedidos_filtrados.xlsx"',
        'Cache-Control' => 'max-age=0',
    ]);
}

}
