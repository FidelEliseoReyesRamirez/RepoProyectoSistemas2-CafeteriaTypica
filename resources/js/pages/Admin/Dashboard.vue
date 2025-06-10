<script setup lang="ts">
import { ref, computed } from 'vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { usePage } from '@inertiajs/vue3';
import axios from 'axios';
import { RefreshCw, Brain, Zap, Download, AlertCircle } from 'lucide-vue-next';

interface ForecastItem { ds: string; yhat: number; real?: number; }
interface VentaDia { dia: string; ventas: number; }
interface ProductoTop { nombre: string; total_vendido: number; }
interface Metrics {
  ventasSemana: number;
  clientesAtendidos: number;
  ticketPromedio: number;
}
interface PageProps {
  forecast: ForecastItem[];
  porProducto: Record<number, ForecastItem[]>;
  topProduct: { nombre: string; cantidad: number };
  metrics: Metrics;
  topProductos: ProductoTop[];
  ventasDiarias: VentaDia[];
  franjaActiva: { hora: number; cantidad: number } | null;
  debug?: {
    forecastExists: boolean;
    totalPedidos: number;
    totalDetalles: number;
    forecastGeneralCount: number;
    forecastProductCount: number;
  };
}

const {
  forecast = [],
  porProducto = {},
  topProduct = { nombre: 'N/A', cantidad: 0 },
  metrics = { ventasSemana: 0, clientesAtendidos: 0, ticketPromedio: 0 },
  topProductos = [],
  ventasDiarias = [],
  franjaActiva = null,
  debug = null
} = usePage().props as unknown as PageProps;

const isGenerating = ref(false);
const lastUpdated = ref(new Date());
const error = ref('');
const success = ref('');

// Computed para verificar si hay datos
const hasData = computed(() => {
  return forecast.length > 0 || Object.keys(porProducto).length > 0;
});

const generarPrediccion = async () => {
  isGenerating.value = true;
  error.value = '';
  success.value = '';

  try {
    console.log('Iniciando generación de predicción...');
    const response = await axios.post('/admin/generar-prediccion');
    console.log('Respuesta del servidor:', response.data);

    success.value = 'Predicción generada correctamente';
    lastUpdated.value = new Date();

    // Recargar la página después de un breve delay
    setTimeout(() => {
      window.location.reload();
    }, 1500);

  } catch (e: any) {
    console.error('Error al generar predicción:', e);

    if (e.response && e.response.data) {
      error.value = e.response.data.error || 'Error desconocido del servidor';
      console.log('Detalles del error:', e.response.data);
    } else {
      error.value = 'Error de conexión con el servidor';
    }
  } finally {
    isGenerating.value = false;
  }
};

const exportarCSV = async () => {
  try {
    const response = await fetch('/admin/export-csv');

    if (!response.ok) {
      const errorData = await response.json();
      throw new Error(errorData.error || 'Error al exportar CSV');
    }

    const blob = await response.blob();
    const url = window.URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = `forecast_${new Date().toISOString().split('T')[0]}.csv`;
    document.body.appendChild(a);
    a.click();
    window.URL.revokeObjectURL(url);
    document.body.removeChild(a);

    success.value = 'CSV exportado correctamente';
    setTimeout(() => success.value = '', 3000);

  } catch (e: any) {
    console.error('Error al exportar CSV:', e);
    error.value = e.message || 'Error al exportar CSV';
    setTimeout(() => error.value = '', 5000);
  }
};

const debugInfo = async () => {
  try {
    const response = await axios.get('/admin/debug');
    console.log('Debug info:', response.data);
    alert('Información de debug enviada a la consola');
  } catch (e) {
    console.error('Error al obtener debug info:', e);
  }
};
</script>

<template>
  <AppLayout>
    <div class="p-6 space-y-6">
      <div class="flex justify-between items-center">
        <div>
          <h1 class="text-2xl font-bold">Dashboard de Predicciones</h1>
          <p class="text-gray-500">Actualizado: {{ lastUpdated.toLocaleString() }}</p>
          <p v-if="!hasData" class="text-yellow-600 flex items-center gap-2 mt-2">
            <AlertCircle class="w-4 h-4" />
            No hay datos de predicción disponibles. Genere una nueva predicción.
          </p>
        </div>
        <div class="flex gap-4">
          <button
            :disabled="isGenerating"
            @click="generarPrediccion"
            class="flex items-center gap-2 px-4 py-2 rounded-lg text-white bg-blue-600 hover:bg-blue-700 disabled:bg-gray-300 disabled:cursor-not-allowed"
          >
            <component :is="isGenerating ? RefreshCw : Brain" class="w-4 h-4" :class="{ 'animate-spin': isGenerating }" />
            <span>{{ isGenerating ? 'Generando...' : 'Generar Predicción' }}</span>
            <Zap class="w-4 h-4" />
          </button>
          <button
            @click="exportarCSV"
            :disabled="!hasData"
            class="flex items-center gap-2 px-4 py-2 text-sm bg-green-600 text-white rounded-lg hover:bg-green-700 disabled:bg-gray-300 disabled:cursor-not-allowed"
          >
            <Download class="w-4 h-4" />
            Exportar CSV
          </button>
          <button
            @click="debugInfo"
            class="px-4 py-2 text-sm bg-gray-600 text-white rounded-lg hover:bg-gray-700"
          >
            Debug
          </button>
        </div>
      </div>

      <!-- Mensajes de éxito y error -->
      <div v-if="success" class="p-4 bg-green-100 border border-green-200 text-green-800 rounded-lg">
        {{ success }}
      </div>
      <div v-if="error" class="p-4 bg-red-100 border border-red-200 text-red-800 rounded-lg">
        {{ error }}
      </div>

      <!-- Métricas -->
      <div v-if="metrics" class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <div class="p-4 border rounded-xl bg-white">
          <p class="text-sm text-gray-500">Ventas Semana</p>
          <p class="text-xl font-bold">${{ metrics.ventasSemana }}</p>
        </div>
        <div class="p-4 border rounded-xl bg-white">
          <p class="text-sm text-gray-500">Clientes Atendidos</p>
          <p class="text-xl font-bold">{{ metrics.clientesAtendidos }}</p>
        </div>
        <div class="p-4 border rounded-xl bg-white">
          <p class="text-sm text-gray-500">Ticket Promedio</p>
          <p class="text-xl font-bold">${{ metrics.ticketPromedio }}</p>
        </div>
        <div class="p-4 border rounded-xl bg-white">
          <p class="text-sm text-gray-500">Producto Más Vendido</p>
          <p class="text-xl font-bold">{{ topProduct.nombre }} ({{ topProduct.cantidad }})</p>
        </div>
      </div>

      <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <!-- Gráfico Predicción General -->
        <div class="p-4 bg-white rounded-xl border">
          <h3 class="text-lg font-semibold mb-2">Predicción General (7 días)</h3>
          <div v-if="forecast.length > 0">
            <img
              :src="`https://quickchart.io/chart?c=${encodeURIComponent(JSON.stringify({
                type: 'line',
                data: {
                  labels: forecast.map((f: any) => new Date(f.ds).toLocaleDateString()),
                  datasets: [
                    {
                      label: 'Predicción',
                      data: forecast.map((f: any) => Math.round(f.yhat)),
                      borderColor: '#3B82F6',
                      backgroundColor: 'rgba(59, 130, 246, 0.1)',
                      tension: 0.4
                    },
                    {
                      label: 'Real',
                      data: forecast.map((f: any) => f.real || null),
                      borderColor: '#10B981',
                      backgroundColor: 'rgba(16, 185, 129, 0.1)',
                      tension: 0.4
                    }
                  ]
                },
                options: {
                  responsive: true,
                  scales: {
                    y: {
                      beginAtZero: true,
                      title: { display: true, text: 'Cantidad' }
                    }
                  }
                }
              }))}`"
              alt="Predicción General"
              class="w-full rounded-xl"
              style="max-height: 300px;"
            />
          </div>
          <div v-else class="text-center text-gray-500 py-8">
            <AlertCircle class="w-12 h-12 mx-auto mb-2 text-gray-400" />
            <p>No hay datos de predicción general disponibles</p>
          </div>
        </div>

        <!-- Ventas diarias -->
        <div class="p-4 bg-white rounded-xl border">
          <h3 class="text-lg font-semibold mb-2">Tendencia Diaria (90 días)</h3>
          <div v-if="ventasDiarias.length > 0">
            <img
              :src="`https://quickchart.io/chart?c=${encodeURIComponent(JSON.stringify({
                type: 'bar',
                data: {
                  labels: ventasDiarias.slice(-30).map((v: any) => new Date(v.dia).toLocaleDateString()),
                  datasets: [{
                    label: 'Ventas',
                    data: ventasDiarias.slice(-30).map((v: any) => v.ventas),
                    backgroundColor: 'rgba(16, 185, 129, 0.6)',
                    borderColor: '#10B981',
                    borderWidth: 1
                  }]
                },
                options: {
                  responsive: true,
                  scales: {
                    y: {
                      beginAtZero: true,
                      title: { display: true, text: 'Número de Ventas' }
                    }
                  }
                }
              }))}`"
              alt="Ventas por Día"
              class="w-full rounded-xl"
              style="max-height: 300px;"
            />
          </div>
          <div v-else class="text-center text-gray-500 py-8">
            <AlertCircle class="w-12 h-12 mx-auto mb-2 text-gray-400" />
            <p>No hay datos de ventas diarias disponibles</p>
          </div>
        </div>
      </div>

      <!-- Predicción por producto -->
      <div class="p-4 bg-white border rounded-xl">
        <h3 class="text-lg font-semibold mb-4">Predicción por Producto</h3>
        <div v-if="Object.keys(porProducto).length === 0" class="text-center text-gray-500 py-4">
          <AlertCircle class="w-8 h-8 mx-auto mb-2 text-gray-400" />
          <p>No hay datos de predicción por producto disponibles</p>
        </div>
        <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
          <div v-for="(valores, id) in porProducto" :key="id" class="p-3 border rounded-lg bg-gray-50">
            <h4 class="font-medium mb-2">Producto ID: {{ id }}</h4>
            <div class="text-sm text-gray-600">
              <p>Próxima semana: <span class="font-bold text-blue-600">{{ Math.round(valores[valores.length - 1]?.yhat || 0) }}</span> unidades</p>
              <p>Registros: {{ valores.length }} días</p>
            </div>
          </div>
        </div>
      </div>

      <!-- Top Productos -->
      <div class="p-4 bg-white border rounded-xl">
        <h3 class="text-lg font-semibold mb-4">Top Productos (Último Mes)</h3>
        <div v-if="topProductos.length === 0" class="text-center text-gray-500 py-4">
          <AlertCircle class="w-8 h-8 mx-auto mb-2 text-gray-400" />
          <p>No hay datos de ventas de productos disponibles</p>
        </div>
        <div v-else class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div v-for="(item, i) in topProductos" :key="i" class="flex items-center justify-between p-3 border rounded-lg">
            <div>
              <p class="font-medium">{{ item.nombre }}</p>
              <p class="text-sm text-gray-500">{{ item.total_vendido }} unidades vendidas</p>
            </div>
            <div class="text-right">
              <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                #{{ i + 1 }}
              </span>
            </div>
          </div>
        </div>
      </div>

      <!-- Franja horaria más activa -->
      <div class="p-4 bg-white border rounded-xl">
        <h3 class="text-lg font-semibold mb-2">Franja Horaria Más Activa</h3>
        <div v-if="franjaActiva" class="flex items-center gap-4">
          <div class="p-3 bg-blue-50 rounded-lg">
            <p class="text-2xl font-bold text-blue-600">{{ franjaActiva.hora }}:00</p>
          </div>
          <div>
            <p class="text-gray-600">{{ franjaActiva.cantidad }} pedidos en esta hora</p>
            <p class="text-sm text-gray-500">Hora pico de actividad</p>
          </div>
        </div>
        <div v-else class="text-center text-gray-500 py-4">
          <AlertCircle class="w-8 h-8 mx-auto mb-2 text-gray-400" />
          <p>No hay datos de franjas horarias disponibles</p>
        </div>
      </div>

      <!-- Debug Info (temporal) -->
      <div v-if="debug" class="p-4 bg-yellow-50 border border-yellow-200 rounded-xl">
        <h3 class="text-lg font-semibold mb-2 text-yellow-800">Información de Debug</h3>
        <div class="grid grid-cols-2 md:grid-cols-3 gap-4 text-sm">
          <div class="p-2 bg-white rounded">
            <span class="font-medium">Archivo Forecast:</span>
            <span :class="debug.forecastExists ? 'text-green-600' : 'text-red-600'">
              {{ debug.forecastExists ? '✓ Existe' : '✗ No existe' }}
            </span>
          </div>
          <div class="p-2 bg-white rounded">
            <span class="font-medium">Total Pedidos:</span>
            <span class="text-blue-600">{{ debug.totalPedidos }}</span>
          </div>
          <div class="p-2 bg-white rounded">
            <span class="font-medium">Total Detalles:</span>
            <span class="text-blue-600">{{ debug.totalDetalles }}</span>
          </div>
          <div class="p-2 bg-white rounded">
            <span class="font-medium">Forecast General:</span>
            <span class="text-purple-600">{{ debug.forecastGeneralCount }} registros</span>
          </div>
          <div class="p-2 bg-white rounded">
            <span class="font-medium">Forecast Productos:</span>
            <span class="text-purple-600">{{ debug.forecastProductCount }} productos</span>
          </div>
        </div>
        <div class="mt-2 p-2 bg-white rounded text-xs">
          <p><strong>Estado:</strong> {{ hasData ? '✓ Datos cargados correctamente' : '⚠ Sin datos de predicción' }}</p>
        </div>
      </div>
    </div>
  </AppLayout>
</template>
