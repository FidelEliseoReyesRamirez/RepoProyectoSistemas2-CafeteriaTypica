import pandas as pd
from prophet import Prophet
import mysql.connector
import json
from datetime import datetime
import os
import numpy as np

# Conexión MySQL
conn = mysql.connector.connect(
    host='127.0.0.1',
    user='root',
    password='',
    database='typica_bd',
    port=3308
)

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
df_total = pd.read_sql("""
SELECT DATE(p.fecha_hora_registro) AS ds, SUM(dp.cantidad) AS y
FROM pedido p
JOIN detallepedido dp ON p.id_pedido = dp.id_pedido
WHERE p.eliminado = 0 AND dp.eliminado = 0
GROUP BY ds ORDER BY ds
""", conn)

if len(df_total) >= 2:
    model = Prophet(holidays=feriados)
    model.fit(df_total)
    future = model.make_future_dataframe(periods=7)
    forecast = model.predict(future)
    result_total = forecast[['ds', 'yhat']].copy()
    result_total['ds'] = result_total['ds'].astype(str)
    result_total['real'] = result_total['ds'].map(df_total.set_index('ds')['y'].to_dict())
    result_total = result_total.replace({np.nan: None})
else:
    result_total = pd.DataFrame(columns=['ds', 'yhat', 'real'])

# 2. Predicción por producto
df_prod = pd.read_sql("""
SELECT DATE(p.fecha_hora_registro) AS ds, dp.id_producto, SUM(dp.cantidad) AS y
FROM pedido p
JOIN detallepedido dp ON dp.id_pedido = p.id_pedido
WHERE p.eliminado = 0 AND dp.eliminado = 0
GROUP BY ds, dp.id_producto
ORDER BY ds
""", conn)

forecast_productos = {}
for pid in df_prod['id_producto'].unique():
    df_p = df_prod[df_prod['id_producto'] == pid][['ds', 'y']]
    df_p['ds'] = pd.to_datetime(df_p['ds'])

    if len(df_p) >= 2:
        try:
            m = Prophet()
            m.fit(df_p)
            fut = m.make_future_dataframe(periods=7)
            pred = m.predict(fut)
            result_prod = pred[['ds', 'yhat']].copy()
            result_prod['ds'] = result_prod['ds'].astype(str)
            result_prod = result_prod.replace({np.nan: None})
            forecast_productos[int(pid)] = result_prod.to_dict(orient='records')
        except Exception as e:
            print(f"Error en predicción para producto {pid}: {e}")
            continue

# 3. Guardar resultados
output = {
    'general': result_total.to_dict(orient='records') if len(result_total) > 0 else [],
    'por_producto': forecast_productos
}

# Crear directorio si no existe
output_dir = os.path.join(os.path.dirname(__file__), '../storage/app')
os.makedirs(output_dir, exist_ok=True)

with open(os.path.join(output_dir, 'forecast.json'), 'w') as f:
    json.dump(output, f, indent=2, default=str)

print("Predicciones guardadas correctamente")
print(f"- Predicción general: {len(result_total)} registros")
print(f"- Predicciones por producto: {len(forecast_productos)} productos")

conn.close()
