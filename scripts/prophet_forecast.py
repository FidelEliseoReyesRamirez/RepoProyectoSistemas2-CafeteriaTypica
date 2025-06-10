import pandas as pd
from prophet import Prophet
import mysql.connector
import json
from datetime import datetime
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

    # 1. Predicción general (ventas totales por día)
    print("Consultando datos generales...")
    df_total = pd.read_sql("""
    SELECT DATE(p.fecha_hora_registro) AS ds, SUM(dp.cantidad) AS y
    FROM pedido p
    JOIN detallepedido dp ON p.id_pedido = dp.id_pedido
    WHERE p.eliminado = 0 AND dp.eliminado = 0
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

        # Mapear valores reales
        real_values = df_total.set_index('ds')['y'].to_dict()
        result_total['real'] = result_total['ds'].map(real_values)
        result_total = result_total.replace({np.nan: None})
        print(f"✓ Predicción general completada: {len(result_total)} registros")
    else:
        print("⚠ Datos insuficientes para predicción general")
        result_total = pd.DataFrame(columns=['ds', 'yhat', 'real'])

    # 2. Predicción por producto
    print("Consultando datos por producto...")
    df_prod = pd.read_sql("""
    SELECT DATE(p.fecha_hora_registro) AS ds, dp.id_producto, SUM(dp.cantidad) AS y
    FROM pedido p
    JOIN detallepedido dp ON dp.id_pedido = p.id_pedido
    WHERE p.eliminado = 0 AND dp.eliminado = 0
    GROUP BY ds, dp.id_producto
    ORDER BY ds
    """, conn)

    print(f"✓ Datos por producto obtenidos: {len(df_prod)} registros")

    forecast_productos = {}
    productos_unicos = df_prod['id_producto'].unique()
    print(f"Procesando {len(productos_unicos)} productos...")

    for i, pid in enumerate(productos_unicos):
        try:
            df_p = df_prod[df_prod['id_producto'] == pid][['ds', 'y']]
            df_p['ds'] = pd.to_datetime(df_p['ds'])
            df_p = df_p.groupby('ds').sum().reset_index()  # Agrupar por fecha

            if len(df_p) >= 2:
                m = Prophet()
                m.fit(df_p)
                fut = m.make_future_dataframe(periods=7)
                pred = m.predict(fut)
                result_prod = pred[['ds', 'yhat']].copy()
                result_prod['ds'] = result_prod['ds'].dt.strftime('%Y-%m-%d')
                result_prod = result_prod.replace({np.nan: None})
                forecast_productos[int(pid)] = result_prod.to_dict(orient='records')
                print(f"✓ Producto {pid} procesado ({i+1}/{len(productos_unicos)})")
            else:
                print(f"⚠ Producto {pid}: datos insuficientes")

        except Exception as e:
            print(f"✗ Error en predicción para producto {pid}: {e}")
            continue

    print(f"✓ Predicciones por producto completadas: {len(forecast_productos)} productos")

    # 3. Guardar resultados
    output = {
        'general': result_total.to_dict(orient='records') if len(result_total) > 0 else [],
        'por_producto': forecast_productos
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
    print(f"- Archivo generado: {output_file}")

    # Opcional: Generar también los CSV que mencionas
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
