import pandas as pd
from prophet import Prophet
import mysql.connector
import json
from datetime import datetime
import os

# Conexión
conn = mysql.connector.connect(
    host='127.0.0.1',
    user='root',
    password='',
    database='typica_bd'
)

# ------------------------------
# 1. DEMANDA GENERAL
# ------------------------------
df_total = pd.read_sql("""
SELECT DATE(p.fecha_hora_registro) AS ds, SUM(dp.cantidad) AS y
FROM pedido p
JOIN detallepedido dp ON p.id_pedido = dp.id_pedido
WHERE p.eliminado = 0 AND dp.eliminado = 0
GROUP BY DATE(p.fecha_hora_registro)
ORDER BY ds
""", conn)

model = Prophet()

# FERIADOS DE BOLIVIA
holidays = pd.DataFrame({
    'holiday': 'feriado',
    'ds': pd.to_datetime([
        '2025-01-01', '2025-02-20', '2025-02-21',  # Año nuevo, Carnaval
        '2025-04-18', '2025-05-01',                # Viernes Santo, Día del Trabajador
        '2025-06-21', '2025-08-06',                # Año Nuevo Aymara, Independencia
        '2025-11-02', '2025-12-25'                 # Todos los Santos, Navidad
    ]),
    'lower_window': 0,
    'upper_window': 1,
})
model = Prophet(holidays=holidays)

model.fit(df_total)
future = model.make_future_dataframe(periods=7)
forecast_total = model.predict(future)

result_total = forecast_total[['ds', 'yhat']].copy()
result_total['ds'] = result_total['ds'].astype(str)
result_total['real'] = result_total['ds'].map(df_total.set_index('ds')['y'].to_dict())

# ------------------------------
# 2. PREDICCIÓN POR PRODUCTO
# ------------------------------
df_productos = pd.read_sql("""
SELECT DATE(p.fecha_hora_registro) AS ds, dp.id_producto, SUM(dp.cantidad) AS y
FROM pedido p
JOIN detallepedido dp ON p.id_pedido = dp.id_pedido
WHERE p.eliminado = 0 AND dp.eliminado = 0
GROUP BY ds, dp.id_producto
""", conn)

productos = df_productos['id_producto'].unique()
forecast_productos = {}

for pid in productos:
    df_p = df_productos[df_productos['id_producto'] == pid][['ds', 'y']]
    df_p['ds'] = pd.to_datetime(df_p['ds'])
    if len(df_p) >= 2:
        m = Prophet()
        m.fit(df_p)
        future = m.make_future_dataframe(periods=7)
        forecast = m.predict(future)
        forecast_productos[int(pid)] = forecast[['ds', 'yhat']].to_dict(orient='records')

# ------------------------------
# 3. PREDICCIÓN POR COMBO (simulado por IDs comunes)
# ------------------------------
combos = {
    "combo_1": [1, 4, 5],   # Café Americano + Jugo + Cheesecake
    "combo_2": [2, 68, 21], # Latte + Empanada Queso + Coldbrew
}

forecast_combos = {}

for cname, items in combos.items():
    df_combo = df_productos[df_productos['id_producto'].isin(items)].groupby('ds').agg({'y': 'sum'}).reset_index()
    df_combo['ds'] = pd.to_datetime(df_combo['ds'])
    if len(df_combo) >= 2:
        m = Prophet()
        m.fit(df_combo)
        future = m.make_future_dataframe(periods=7)
        forecast = m.predict(future)
        forecast_combos[cname] = forecast[['ds', 'yhat']].to_dict(orient='records')

conn.close()

# ------------------------------
# GUARDAR JSON
# ------------------------------
output_path = os.path.join(os.path.dirname(__file__), '../storage/app/forecast.json')
with open(output_path, 'w') as f:
    json.dump({
        'general': result_total.to_dict(orient='records'),
        'por_producto': forecast_productos,
        'por_combo': forecast_combos,
    }, f, indent=2, default=str)
print(f"Forecast saved to {output_path}")
# ------------------------------
# FIN DEL SCRIPT
# ------------------------------
