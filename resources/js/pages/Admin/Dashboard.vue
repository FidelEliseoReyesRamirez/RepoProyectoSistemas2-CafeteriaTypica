<script setup lang="ts">
import { ref } from 'vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { usePage } from '@inertiajs/vue3';
import axios from 'axios';
import { RefreshCw, Brain, Zap } from 'lucide-vue-next';

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

const generarPrediccion = async () => {
  isGenerating.value = true;
  try {
    await axios.post('/admin/generar-prediccion');
    lastUpdated.value = new Date();
    window.location.reload();
  } catch (e) {
    console.error('Error al generar predicción', e);
  } finally {
    isGenerating.value = false;
  }
};

const exportarCSV = () => {
  window.open('/admin/export-csv', '_blank');
};
</script>

<template>
  <AppLayout>
    <div class="p-6 space-y-6">
      <div class="flex justify-between items-center">
        <div>
          <h1 class="text-2xl font-bold">Dashboard de Predicciones</h1>
          <p class="text-gray-500">Actualizado: {{ lastUpdated.toLocaleString() }}</p>
        </div>
        <div class="flex gap-4">
          <button
            :disabled="isGenerating"
            @click="generarPrediccion"
            class="flex items-center gap-2 px-4 py-2 rounded-lg text-white bg-blue-600 hover:bg-blue-700 disabled:bg-gray-300"
          >
            <component :is="isGenerating ? RefreshCw : Brain" class="w-4 h-4" :class="{ 'animate-spin': isGenerating }" />
            <span>{{ isGenerating ? 'Generando...' : 'Generar Predicción' }}</span>
            <Zap class="w-4 h-4" />
          </button>
          <button @click="exportarCSV" class="px-4 py-2 text-sm bg-green-600 text-white rounded-lg">Exportar CSV</button>
        </div>
      </div>

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
          <h3 class="text-lg font-semibold mb-2">Predicción General</h3>
          <img :src="`https://quickchart.io/chart?c=${encodeURIComponent(JSON.stringify({
            type: 'line',
            data: {
              labels: forecast.map((f:any) => f.ds),
              datasets: [
                { label: 'Predicción', data: forecast.map((f:any) => f.yhat), borderColor: '#3B82F6' },
                { label: 'Real', data: forecast.map((f:any) => f.real || null), borderColor: '#10B981' }
              ]
            }
          }))}`" alt="Predicción General" class="w-full rounded-xl" />
        </div>

        <!-- Ventas diarias -->
        <div class="p-4 bg-white rounded-xl border">
          <h3 class="text-lg font-semibold mb-2">Tendencia Diaria (90 días)</h3>
          <img :src="`https://quickchart.io/chart?c=${encodeURIComponent(JSON.stringify({
            type: 'bar',
            data: {
              labels: ventasDiarias.map((v:any) => v.dia),
              datasets: [{ label: 'Ventas', data: ventasDiarias.map((v:any)=> v.ventas), backgroundColor: '#10B981' }]
            }
          }))}`" alt="Ventas por Día" class="w-full rounded-xl" />
        </div>
      </div>

      <!-- Predicción por producto -->
      <div class="p-4 bg-white border rounded-xl">
        <h3 class="text-lg font-semibold mb-2">Predicción por Producto</h3>
        <div v-if="Object.keys(porProducto).length === 0" class="text-gray-500 italic">
          No hay datos de predicción por producto disponibles
        </div>
        <ul v-else class="space-y-1">
          <li v-for="(valores, id) in porProducto" :key="id">
            Producto {{ id }}: {{ valores.at(-1)?.yhat?.toFixed(2) }} unidades estimadas
          </li>
        </ul>
      </div>

      <!-- Top Productos -->
      <div class="p-4 bg-white border rounded-xl">
        <h3 class="text-lg font-semibold mb-2">Top Productos (Último Mes)</h3>
        <div v-if="topProductos.length === 0" class="text-gray-500 italic">
          No hay datos de ventas disponibles
        </div>
        <ul v-else class="list-disc list-inside space-y-1">
          <li v-for="(item, i) in topProductos" :key="i">
            {{ item.nombre }} - {{ item.total_vendido }} unidades vendidas
          </li>
        </ul>
      </div>

      <!-- Debug Info (temporal) -->
      <div v-if="debug" class="p-4 bg-yellow-50 border border-yellow-200 rounded-xl">
        <h3 class="text-lg font-semibold mb-2 text-yellow-800">Información de Debug</h3>
        <div class="grid grid-cols-2 md:grid-cols-3 gap-4 text-sm">
          <div>
            <span class="font-medium">Archivo Forecast:</span>
            <span :class="debug.forecastExists ? 'text-green-600' : 'text-red-600'">
              {{ debug.forecastExists ? 'Existe' : 'No existe' }}
            </span>
          </div>
          <div>
            <span class="font-medium">Total Pedidos:</span>
            <span class="text-blue-600">{{ debug.totalPedidos }}</span>
          </div>
          <div>
            <span class="font-medium">Total Detalles:</span>
            <span class="text-blue-600">{{ debug.totalDetalles }}</span>
          </div>
          <div>
            <span class="font-medium">Forecast General:</span>
            <span class="text-purple-600">{{ debug.forecastGeneralCount }} registros</span>
          </div>
          <div>
            <span class="font-medium">Forecast Productos:</span>
            <span class="text-purple-600">{{ debug.forecastProductCount }} productos</span>
          </div>
        </div>
      </div>

      <!-- Franja horaria más activa -->
      <div class="p-4 bg-white border rounded-xl">
        <h3 class="text-lg font-semibold mb-2">Franja Horaria Más Activa</h3>
        <p v-if="franjaActiva" class="text-gray-600">
          {{ franjaActiva.hora }}:00 con {{ franjaActiva.cantidad }} pedidos
        </p>
        <p v-else class="text-gray-500 italic">No hay datos disponibles</p>
      </div>
    </div>
  </AppLayout>
</template>
