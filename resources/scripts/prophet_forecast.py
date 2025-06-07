import pandas as pd
from prophet import Prophet
import mysql.connector
import os

# Feriados de Bolivia 2025
bolivia_holidays = pd.DataFrame({
    'holiday': [
        'Año Nuevo', 'Día del Estado Plurinacional', 'Carnaval Lunes', 'Carnaval Martes',
        'Viernes Santo', 'Día del Trabajo', 'Corpus Christi', 'Año Nuevo Andino',
        'Día de la Independencia', 'Todos los Santos', 'Navidad'
    ],
    'ds': pd.to_datetime([
        '2025-01-01', '2025-01-22', '2025-03-03', '2025-03-04',
        '2025-04-18', '2025-05-01', '2025-06-19', '2025-06-21',
        '2025-08-06', '2025-11-01', '2025-12-25'
    ]),
    'lower_window': 0,
    'upper_window': 1
})

# Conexión a MySQL
conn = mysql.connector.connect(
    host='127.0.0.1',
    user='root',
    password='',
    database='typica_bd'
)

# Obtener la demanda por día
query = """
SELECT DATE(p.fecha_hora_registro) AS ds, SUM(dp.cantidad) AS y
FROM pedido p
JOIN detallepedido dp ON dp.id_pedido = p.id_pedido
WHERE p.eliminado = 0 AND dp.eliminado = 0
GROUP BY DATE(p.fecha_hora_registro)
ORDER BY ds
"""
df = pd.read_sql(query, conn)

# Crear modelo Prophet con feriados
model = Prophet(holidays=bolivia_holidays)
model.fit(df)

# Predecir 7 días futuros
future = model.make_future_dataframe(periods=7)
forecast = model.predict(future)

# Preparar resultados
result = forecast[['ds', 'yhat']].copy()
result['ds'] = result['ds'].astype(str)
result['real'] = result['ds'].map(df.set_index('ds')['y'].to_dict())

# Guardar a forecast.json
output_path = os.path.join(os.path.dirname(__file__), '../storage/app/forecast.json')
result.to_json(output_path, orient='records', indent=2)

# Guardar también en tabla prediccion
cursor = conn.cursor()
cursor.execute("DELETE FROM prediccion WHERE id_producto = 0")

for row in result.itertuples():
    cursor.execute("""
        INSERT INTO prediccion (id_producto, fecha_predicha, demanda_prevista, tipo_sugerencia, sugerencia_descripcion)
        VALUES (%s, %s, %s, %s, %s)
    """, (
        0,
        row.ds,
        int(row.yhat),
        'general',
        'Predicción general diaria con Prophet'
    ))

conn.commit()
cursor.close()
conn.close()
