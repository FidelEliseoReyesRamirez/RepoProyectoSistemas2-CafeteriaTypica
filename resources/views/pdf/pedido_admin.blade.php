<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Comanda Pedido #{{ $pedido->id_pedido }}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
        }
        .container {
            width: 100%;
            margin: 0 auto;
            padding: 10px;
        }
        h1, h2, h3 {
            text-align: center;
        }
        .order-details {
            margin: 20px 0;
        }
        .order-details p {
            margin: 5px 0;
        }
        .order-items {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        .order-items th, .order-items td {
            padding: 8px;
            text-align: left;
            border: 1px solid #ddd;
        }
        .total {
            text-align: right;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Comanda Pedido #{{ $pedido->id_pedido }}</h1>
        <h3>Fecha de Registro: {{ \Carbon\Carbon::parse($pedido->fecha_hora_registro)->format('d/m/Y H:i') }}</h3>

        <div class="order-details">
            <p><strong>Mesero:</strong> {{ $pedido->usuario ? $pedido->usuario->nombre : 'No asignado' }}</p>
            <p><strong>Estado:</strong> {{ $pedido->estadopedido->nombre_estado }}</p>
        </div>

        <table class="order-items">
            <thead>
                <tr>
                    <th>Producto</th>
                    <th>Categoría</th>
                    <th>Cantidad</th>
                    <th>Precio</th>
                    <th>Total</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($pedido->detallepedidos as $detalle)
                    <tr>
                        <td>{{ $detalle->producto->nombre }}</td>
                        <td>{{ $detalle->producto->categorium->nombre ?? 'Sin categoría' }}</td> <!-- Acceso a 'categorium' -->
                        <td>{{ $detalle->cantidad }}</td>
                        <td>{{ number_format($detalle->producto->precio, 2) }} Bs</td>
                        <td>{{ number_format($detalle->producto->precio * $detalle->cantidad, 2) }} Bs</td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <div class="total">
            <p>Total: {{ number_format($pedido->detallepedidos->sum(fn($item) => $item->producto->precio * $item->cantidad), 2) }} Bs</p>
        </div>
    </div>
</body>
</html>
