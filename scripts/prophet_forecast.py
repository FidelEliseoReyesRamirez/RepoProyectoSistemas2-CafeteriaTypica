import pandas as pd
from prophet import Prophet
import mysql.connector
import json
from datetime import datetime, timedelta
import os
import sys
import numpy as np

try:
    print("Iniciando script de predicciones Prophet...")

    # Conexión MySQL
    print("Conectando a la base de datos...")
    conn = mysql.connector.connect(
        host='127.0.0.1',
        user='root',
        password='',
        database='typica_bd',
        port=3308
    )
    print("✓ Conexión exitosa")

    # Feriados Bolivia
    feriados = pd.DataFrame({
        'holiday': 'feriado',
        'ds': pd.to_datetime([
            '2025-01-01', '2025-02-20', '2025-02-21',
            '2025-04-18', '2025-05-01', '2025-06-21',
            '2025-08-06', '2025-11-02', '2025-12-25'
        ]),
        'lower_window': 0,
        'upper_window': 1
    })

    # 1. Predicción general (ventas totales por día) - SOLO PEDIDOS PAGADOS (estado_actual = 6)
    print("Consultando datos generales...")
    df_total = pd.read_sql("""
    SELECT DATE(p.fecha_hora_registro) AS ds, SUM(dp.cantidad) AS y
    FROM pedido p
    JOIN detallepedido dp ON p.id_pedido = dp.id_pedido
    WHERE p.eliminado = 0 AND dp.eliminado = 0 AND p.estado_actual = 6
    GROUP BY ds ORDER BY ds
    """, conn)

    print(f"✓ Datos generales obtenidos: {len(df_total)} registros")

    if len(df_total) >= 2:
        print("Generando predicción general...")
        model = Prophet(holidays=feriados)
        model.fit(df_total)
        future = model.make_future_dataframe(periods=7)
        forecast = model.predict(future)
        result_total = forecast[['ds', 'yhat']].copy()
        result_total['ds'] = result_total['ds'].dt.strftime('%Y-%m-%d')

        # Mapear valores reales de la base de datos
        real_values = {}
        for _, row in df_total.iterrows():
            real_values[row['ds'].strftime('%Y-%m-%d')] = int(row['y'])

        result_total['real'] = result_total['ds'].map(real_values)
        result_total = result_total.replace({np.nan: None})
        print(f"✓ Predicción general completada: {len(result_total)} registros")
    else:
        print("⚠ Datos insuficientes para predicción general")
        result_total = pd.DataFrame(columns=['ds', 'yhat', 'real'])

    # 2. Predicción por producto con segmentación ABC
    print("Consultando datos por producto...")
    df_prod = pd.read_sql("""
    SELECT DATE(p.fecha_hora_registro) AS ds, dp.id_producto, pr.nombre,
           SUM(dp.cantidad) AS y
    FROM pedido p
    JOIN detallepedido dp ON dp.id_pedido = p.id_pedido
    JOIN producto pr ON dp.id_producto = pr.id_producto
    WHERE p.eliminado = 0 AND dp.eliminado = 0 AND pr.eliminado = 0 AND p.estado_actual = 6
    GROUP BY ds, dp.id_producto, pr.nombre
    ORDER BY ds
    """, conn)

    print(f"✓ Datos por producto obtenidos: {len(df_prod)} registros")

    # Análisis ABC para segmentación inteligente
    print("Realizando segmentación ABC...")
    ventas_por_producto = df_prod.groupby(['id_producto', 'nombre'])['y'].sum().reset_index()
    ventas_por_producto = ventas_por_producto.sort_values('y', ascending=False)

    total_ventas = ventas_por_producto['y'].sum()
    ventas_por_producto['porcentaje'] = (ventas_por_producto['y'] / total_ventas * 100).cumsum()

    # Asignar categorías ABC
    def asignar_categoria(porcentaje):
        if porcentaje <= 70:
            return 'A'  # Top performers (70% de ventas)
        elif porcentaje <= 90:
            return 'B'  # Medio (20% de ventas)
        else:
            return 'C'  # Bajo (10% de ventas)

    ventas_por_producto['categoria'] = ventas_por_producto['porcentaje'].apply(asignar_categoria)
    categoria_map = dict(zip(ventas_por_producto['id_producto'], ventas_por_producto['categoria']))

    forecast_productos = {}
    productos_unicos = df_prod['id_producto'].unique()
    print(f"Procesando {len(productos_unicos)} productos...")

    for i, pid in enumerate(productos_unicos):
        try:
            df_p = df_prod[df_prod['id_producto'] == pid][['ds', 'y', 'nombre']].copy()
            nombre_producto = df_p['nombre'].iloc[0]

            df_p['ds'] = pd.to_datetime(df_p['ds'])
            df_p = df_p.groupby('ds')['y'].sum().reset_index()  # Agrupar por fecha

            if len(df_p) >= 2:
                m = Prophet()
                m.fit(df_p)
                fut = m.make_future_dataframe(periods=7)
                pred = m.predict(fut)

                # Obtener solo las predicciones futuras (próximos 7 días)
                future_predictions = pred.tail(7)[['ds', 'yhat']].copy()
                future_predictions['ds'] = future_predictions['ds'].dt.strftime('%Y-%m-%d')
                future_predictions = future_predictions.replace({np.nan: None})

                # Agregar información del producto
                producto_info = {
                    'id': int(pid),
                    'nombre': nombre_producto,
                    'categoria': categoria_map.get(pid, 'C'),
                    'prediccion': float(pred.tail(7)['yhat'].mean())  # Promedio de próximos 7 días
                }

                forecast_productos[int(pid)] = [producto_info]  # Array con un elemento para mantener estructura
                print(f"✓ Producto {nombre_producto} procesado ({i+1}/{len(productos_unicos)})")
            else:
                print(f"⚠ Producto {pid}: datos insuficientes")

        except Exception as e:
            print(f"✗ Error en predicción para producto {pid}: {e}")
            continue

    print(f"✓ Predicciones por producto completadas: {len(forecast_productos)} productos")

    # 3. Análisis de tendencias
    print("Analizando producto en tendencia...")

    # Comparar último mes vs mes anterior
    fecha_actual = datetime.now()
    inicio_mes_actual = fecha_actual.replace(day=1) - timedelta(days=30)
    inicio_mes_anterior = inicio_mes_actual - timedelta(days=30)

    tendencia_query = """
    SELECT pr.nombre,
           SUM(CASE WHEN p.fecha_hora_registro >= %s THEN dp.cantidad ELSE 0 END) as ventas_actuales,
           SUM(CASE WHEN p.fecha_hora_registro < %s AND p.fecha_hora_registro >= %s THEN dp.cantidad ELSE 0 END) as ventas_anteriores
    FROM pedido p
    JOIN detallepedido dp ON p.id_pedido = dp.id_pedido
    JOIN producto pr ON dp.id_producto = pr.id_producto
    WHERE p.eliminado = 0 AND dp.eliminado = 0 AND pr.eliminado = 0 AND p.estado_actual = 6
    GROUP BY pr.id_producto, pr.nombre
    HAVING ventas_anteriores > 0
    """

    df_tendencia = pd.read_sql(tendencia_query, conn, params=[
        inicio_mes_actual.strftime('%Y-%m-%d'),
        inicio_mes_actual.strftime('%Y-%m-%d'),
        inicio_mes_anterior.strftime('%Y-%m-%d')
    ])

    if len(df_tendencia) > 0:
        df_tendencia['crecimiento'] = ((df_tendencia['ventas_actuales'] - df_tendencia['ventas_anteriores']) / df_tendencia['ventas_anteriores'] * 100).round(1)
        producto_tendencia = df_tendencia.loc[df_tendencia['crecimiento'].idxmax()]

        tendencia_info = {
            'nombre': producto_tendencia['nombre'],
            'crecimiento': float(producto_tendencia['crecimiento']),
            'ventas_actuales': int(producto_tendencia['ventas_actuales']),
            'ventas_anteriores': int(producto_tendencia['ventas_anteriores'])
        }
    else:
        tendencia_info = {
            'nombre': 'N/A',
            'crecimiento': 0,
            'ventas_actuales': 0,
            'ventas_anteriores': 0
        }

    # 4. Productos estacionales (simulado basado en nombres comunes)
    print("Generando alertas estacionales...")
    mes_actual = fecha_actual.month

    productos_estacionales = []

    # Obtener productos activos
    productos_query = "SELECT nombre FROM producto WHERE eliminado = 0"
    df_productos = pd.read_sql(productos_query, conn)

    # Definir patrones estacionales
    patrones_estacionales = {
        'helado': {'meses': [11, 12, 1, 2], 'temporada': 'Verano'},
        'chocolate': {'meses': [6, 7, 8], 'temporada': 'Invierno'},
        'café': {'meses': [6, 7, 8], 'temporada': 'Invierno'},
        'frío': {'meses': [11, 12, 1, 2], 'temporada': 'Verano'},
        'caliente': {'meses': [6, 7, 8], 'temporada': 'Invierno'}
    }

    for _, producto in df_productos.iterrows():
        nombre_lower = producto['nombre'].lower()
        for patron, info in patrones_estacionales.items():
            if patron in nombre_lower and mes_actual in info['meses']:
                productos_estacionales.append({
                    'nombre': producto['nombre'],
                    'temporada': info['temporada'],
                    'alerta': f'Temporada alta para {info["temporada"]}'
                })
                break

    # 5. Alertas de stock (simulado - necesitarías tabla de inventario real)
    print("Generando alertas de stock...")
    alertas_stock = []

    # Simulación basada en productos más vendidos con stock bajo
    stock_query = """
    SELECT pr.nombre, SUM(dp.cantidad) as total_vendido
    FROM pedido p
    JOIN detallepedido dp ON p.id_pedido = dp.id_pedido
    JOIN producto pr ON dp.id_producto = pr.id_producto
    WHERE p.eliminado = 0 AND dp.eliminado = 0 AND pr.eliminado = 0
          AND p.estado_actual = 6
          AND p.fecha_hora_registro >= DATE_SUB(NOW(), INTERVAL 30 DAY)
    GROUP BY pr.id_producto, pr.nombre
    ORDER BY total_vendido DESC
    LIMIT 3
    """

    df_stock = pd.read_sql(stock_query, conn)

    for _, producto in df_stock.iterrows():
        # Simulación de stock bajo para productos top
        stock_actual = max(1, int(producto['total_vendido'] * 0.1))  # 10% del vendido
        stock_minimo = int(producto['total_vendido'] * 0.2)  # 20% del vendido

        if stock_actual < stock_minimo:
            alertas_stock.append({
                'nombre': producto['nombre'],
                'stock_actual': stock_actual,
                'stock_minimo': stock_minimo,
                'dias_restantes': max(1, stock_actual // 2)  # Estimación
            })

    # 6. Guardar resultados
    output = {
        'general': result_total.to_dict(orient='records') if len(result_total) > 0 else [],
        'por_producto': forecast_productos,
        'producto_tendencia': tendencia_info,
        'productos_estacionales': productos_estacionales,
        'alertas_stock': alertas_stock,
        'generado_en': datetime.now().isoformat()
    }

    # Crear directorio si no existe
    output_dir = os.path.join(os.path.dirname(__file__), '../storage/app')
    os.makedirs(output_dir, exist_ok=True)

    output_file = os.path.join(output_dir, 'forecast.json')
    print(f"Guardando resultados en: {output_file}")

    with open(output_file, 'w', encoding='utf-8') as f:
        json.dump(output, f, indent=2, default=str, ensure_ascii=False)

    print("✓ Predicciones guardadas correctamente")
    print(f"- Predicción general: {len(result_total)} registros")
    print(f"- Predicciones por producto: {len(forecast_productos)} productos")
    print(f"- Producto en tendencia: {tendencia_info['nombre']} (+{tendencia_info['crecimiento']}%)")
    print(f"- Productos estacionales: {len(productos_estacionales)}")
    print(f"- Alertas de stock: {len(alertas_stock)}")
    print(f"- Archivo generado: {output_file}")

    # Opcional: Generar también los CSV
    csv_dir = os.path.join(os.path.dirname(__file__), '../storage/app')

    # Generar predicciones.csv
    if len(result_total) > 0:
        predicciones_csv = os.path.join(csv_dir, 'predicciones.csv')
        result_total.to_csv(predicciones_csv, index=False)
        print(f"✓ CSV de predicciones generado: {predicciones_csv}")

    # Generar ventas_prophet.csv (datos históricos)
    if len(df_total) > 0:
        ventas_csv = os.path.join(csv_dir, 'ventas_prophet.csv')
        df_total.to_csv(ventas_csv, index=False)
        print(f"✓ CSV de ventas históricas generado: {ventas_csv}")

    conn.close()
    print("✓ Script completado exitosamente")

except mysql.connector.Error as e:
    print(f"✗ Error de base de datos: {e}")
    sys.exit(1)
except Exception as e:
    print(f"✗ Error general: {e}")
    import traceback
    traceback.print_exc()
    sys.exit(1)
