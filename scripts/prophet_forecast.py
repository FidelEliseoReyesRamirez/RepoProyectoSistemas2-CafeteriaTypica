# Archivo: scripts/prophet_forecast.py

import pandas as pd
from prophet import Prophet
import mysql.connector
import json
from datetime import datetime
import os

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

# 1. Predicción general
df_total = pd.read_sql("""
SELECT DATE(p.fecha_hora_registro) AS ds, SUM(dp.cantidad) AS y
FROM pedido p
JOIN detallepedido dp ON p.id_pedido = dp.id_pedido
WHERE p.eliminado = 0 AND dp.eliminado = 0
GROUP BY ds ORDER BY ds
""", conn)

model = Prophet(holidays=feriados)
model.fit(df_total)
future = model.make_future_dataframe(periods=7)
forecast = model.predict(future)
result_total = forecast[['ds', 'yhat']].copy()
result_total['ds'] = result_total['ds'].astype(str)
result_total['real'] = result_total['ds'].map(df_total.set_index('ds')['y'].to_dict())

# 2. Predicción por producto
df_prod = pd.read_sql("""
SELECT DATE(p.fecha_hora_registro) AS ds, dp.id_producto, SUM(dp.cantidad) AS y
FROM pedido p JOIN detallepedido dp ON dp.id_pedido = p.id_pedido
WHERE p.eliminado = 0 AND dp.eliminado = 0
GROUP BY ds, dp.id_producto
""", conn)
forecast_productos = {}
for pid in df_prod['id_producto'].unique():
    df_p = df_prod[df_prod['id_producto'] == pid][['ds', 'y']]
    df_p['ds'] = pd.to_datetime(df_p['ds'])
    if len(df_p) >= 2:
        m = Prophet()
        m.fit(df_p)
        fut = m.make_future_dataframe(periods=7)
        forecast_productos[int(pid)] = m.predict(fut)[['ds', 'yhat']].to_dict(orient='records')

# 3. Predicción por combo (dinámica desde BD)
combos = pd.read_sql("""
SELECT id_combo, GROUP_CONCAT(id_producto) AS productos
FROM detallecombo GROUP BY id_combo
""", conn)
forecast_combos = {}
for _, row in combos.iterrows():
    ids = list(map(int, row['productos'].split(',')))
    df_c = df_prod[df_prod['id_producto'].isin(ids)].groupby('ds').agg({'y': 'sum'}).reset_index()
    df_c['ds'] = pd.to_datetime(df_c['ds'])
    if len(df_c) >= 2:
        m = Prophet()
        m.fit(df_c)
        fut = m.make_future_dataframe(periods=7)
        forecast_combos[int(row['id_combo'])] = m.predict(fut)[['ds', 'yhat']].to_dict(orient='records')
import numpy as np

# 4. Guardar resultados
output = {
    'general': result_total.to_dict(orient='records'),
    'por_producto': forecast_productos,
    'por_combo': forecast_combos
}
with open(os.path.join(os.path.dirname(__file__), '../storage/app/forecast.json'), 'w') as f:
    json.dump(output, f, indent=2, default=str)
print("Predicciones guardadas")

conn.close()
