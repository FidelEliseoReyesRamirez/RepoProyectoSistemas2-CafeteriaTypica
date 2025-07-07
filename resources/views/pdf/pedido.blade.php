<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Comanda Pedido #{{ $pedido->id_pedido }}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            color: #333;
            margin: 0;
            padding: 10px;
            font-size: 12px;
            width: 300px; /* Ancho ajustado para una comanda tipo ticket */
        }
        h1 {
            font-size: 16px;
            text-align: center;
            margin-bottom: 10px;
        }
        .pedido {
            margin-top: 20px;
        }
        .detalle {
            margin-top: 10px;
        }
        .detalle p {
            margin: 5px 0;
        }
        .producto {
            margin: 5px 0;
        }
        .comentario {
            font-style: italic;
            color: #555;
            margin-top: 5px;
        }
        .total {
            font-weight: bold;
            margin-top: 10px;
            text-align: center;
        }
        .producto-total {
            display: flex;
            justify-content: space-between;
        }
        .footer {
            text-align: center;
            font-size: 10px;
            margin-top: 20px;
        }
    </style>
</head>
<body>
    <h1>Comanda Pedido #{{ $pedido->id_pedido }}</h1>

    <div class="detalle">
        <h3>Detalles:</h3>
        <ul>
            @foreach ($pedido->detallepedidos as $detalle)
                <li class="producto">
                    <strong>{{ $detalle->producto->nombre }}</strong><br>
                    Cantidad: {{ $detalle->cantidad }}<br>
                    <span class="comentario" v-if="detalle.comentario">{{ $detalle->comentario }}</span>
                    <div class="producto-total">
                        <span>Total:</span>
                        <span>{{ number_format($detalle->producto->precio * $detalle->cantidad, 2) }} Bs</span>
                    </div>
                </li>
            @endforeach
        </ul>
    </div>

    <div class="total">
        <h3>Total: {{ number_format($pedido->detallepedidos->sum(function($detalle) { return $detalle->producto->precio * $detalle->cantidad; }), 2) }} Bs</h3>
    </div>

    <div class="footer">
        <p>¡Gracias por su pedido!</p>
    </div>
</body>
</html>
