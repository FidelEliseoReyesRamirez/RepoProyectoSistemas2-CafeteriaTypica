import pandas as pd
from prophet import Prophet
import mysql.connector
import json
from datetime import datetime
import os

# Conexión a MySQL
conn = mysql.connector.connect(
    host='127.0.0.1',
    user='root',
    password='',
    database='typica_db'
)

query = """
SELECT DATE(p.fecha_hora_registro) AS ds, SUM(dp.cantidad) AS y
FROM pedido p
JOIN detallepedido dp ON dp.id_pedido = p.id_pedido
WHERE p.eliminado = 0 AND dp.eliminado = 0
GROUP BY DATE(p.fecha_hora_registro)
ORDER BY ds
"""

df = pd.read_sql(query, conn)
conn.close()

# Crear y entrenar modelo
model = Prophet()
model.fit(df)

future = model.make_future_dataframe(periods=7)
forecast = model.predict(future)

# Filtrar columnas relevantes
result = forecast[['ds', 'yhat']].copy()
result['ds'] = result['ds'].astype(str)
result['real'] = result['ds'].map(df.set_index('ds')['y'].to_dict())

# Guardar a JSON
output_path = os.path.join(os.path.dirname(__file__), '../storage/app/forecast.json')
result.to_json(output_path, orient='records', indent=2)
