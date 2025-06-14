<script setup lang="ts">
import { ref, computed } from 'vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { usePage } from '@inertiajs/vue3';
import axios from 'axios';
import { RefreshCw, Brain, Zap, Download, AlertTriangle, TrendingUp, Calendar, Award, Info } from 'lucide-vue-next';

interface ForecastItem { ds: string; yhat: number; real?: number; }
interface VentaDia { dia: string; ventas: number; }
interface ProductoTop { nombre: string; total_vendido: number; }
interface ProductoTendencia { nombre: string; crecimiento: number; ventas_actuales: number; ventas_anteriores: number; }
interface ProductoEstacional { nombre: string; temporada: string; alerta: string; }
interface AlertaStock { nombre: string; stock_actual: number; stock_minimo: number; dias_restantes: number; }
interface ProductoSegmentado { id: number; nombre: string; categoria: string; prediccion: number; }

interface Metrics {
  ventasSemana: number;
  clientesAtendidos: number;
}

interface PageProps {
  forecast: ForecastItem[];
  porProducto: Record<number, ProductoSegmentado[]>;
  productoTendencia: ProductoTendencia;
  metrics: Metrics;
  topProductos: ProductoTop[];
  ventasDiarias: VentaDia[];
  productosEstacionales: ProductoEstacional[];
  alertasStock: AlertaStock[];
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
  productoTendencia = { nombre: 'N/A', crecimiento: 0, ventas_actuales: 0, ventas_anteriores: 0 },
  metrics = { ventasSemana: 0, clientesAtendidos: 0},
  topProductos = [],
  ventasDiarias = [],
  productosEstacionales = [],
  alertasStock = [],
  debug = null
} = usePage().props as unknown as PageProps;

const isGenerating = ref(false);
const lastUpdated = ref(new Date());
const error = ref('');
const success = ref('');
const showTooltip = ref<string | null>(null);
const selectedCategory = ref('A'); // Para filtrar productos por categoría

// Computed para verificar si hay datos
const hasData = computed(() => {
  return forecast.length > 0 || Object.keys(porProducto).length > 0;
});

// Filtrar productos por categoría seleccionada
const productosFiltered = computed(() => {
  const productos = Object.entries(porProducto).reduce((acc, [key, items]) => {
    const filtered = items.filter(item => item.categoria === selectedCategory.value);
    if (filtered.length > 0) {
      acc[key] = filtered;
    }
    return acc;
  }, {} as Record<string, ProductoSegmentado[]>);
  return productos;
});

// Obtener el mes anterior para mostrar en "Top Productos"
const mesAnterior = computed(() => {
  const fecha = new Date();
  fecha.setMonth(fecha.getMonth() - 1);
  return fecha.toLocaleDateString('es-ES', { month: 'long', year: 'numeric' });
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

const showMetricTooltip = (metric: string) => {
  showTooltip.value = metric;
  setTimeout(() => showTooltip.value = null, 3000);
};

const getTooltipContent = (metric: string) => {
  const tooltips = {
    ventas: `Predicción vs Real: ${forecast.length > 0 ? '+12% vs lo esperado' : 'Sin datos'}`,
    clientes: `Tendencia: ${metrics.clientesAtendidos > 50 ? 'Incremento del 8%' : 'Estable'}`,
    alertas: `${alertasStock.length} productos requieren atención`,
    tendencia: `Crecimiento: +${productoTendencia.crecimiento}% vs período anterior`
  };
  return tooltips[metric as keyof typeof tooltips] || 'Sin información disponible';
};
</script>

<template>
  <AppLayout>
    <div class="p-6 space-y-6">
      <div class="flex justify-between items-center">
        <div>
          <h1 class="text-2xl font-bold flex items-center gap-2">
            <Brain class="w-8 h-8 text-blue-600" />
            Dashboard
          </h1>
          <p class="text-gray-500">Actualizado: {{ lastUpdated.toLocaleString() }}</p>
          <p v-if="!hasData" class="text-yellow-600 flex items-center gap-2 mt-2">
            <AlertTriangle class="w-4 h-4" />
            No hay datos de predicción disponibles. Genere una nueva predicción.
          </p>
        </div>
        <div class="flex gap-4">
          <button
            :disabled="isGenerating"
            @click="generarPrediccion"
            class="flex items-center gap-2 px-4 py-2 rounded-lg text-white bg-blue-600 hover:bg-blue-700 disabled:bg-gray-300 disabled:cursor-not-allowed transition-all duration-200 hover:scale-105"
          >
            <component :is="isGenerating ? RefreshCw : Brain" class="w-4 h-4" :class="{ 'animate-spin': isGenerating }" />
            <span>{{ isGenerating ? 'Generando...' : 'Generar Predicción' }}</span>
            <Zap class="w-4 h-4" />
          </button>
          <button
            @click="exportarCSV"
            :disabled="!hasData"
            class="flex items-center gap-2 px-4 py-2 text-sm bg-green-600 text-white rounded-lg hover:bg-green-700 disabled:bg-gray-300 disabled:cursor-not-allowed transition-all duration-200 hover:scale-105"
          >
            <Download class="w-4 h-4" />
            Exportar CSV
          </button>
        </div>
      </div>

      <!-- Mensajes de éxito y error -->
      <div v-if="success" class="p-4 bg-green-100 border border-green-200 text-green-800 rounded-lg animate-pulse">
        {{ success }}
      </div>
      <div v-if="error" class="p-4 bg-red-100 border border-red-200 text-red-800 rounded-lg animate-pulse">
        {{ error }}
      </div>

      <!-- Métricas Principales -->
      <div v-if="metrics" class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <!-- Ventas Semana -->
        <div
          class="p-4 border rounded-xl bg-gradient-to-br from-blue-50 to-blue-100 hover:shadow-lg transition-all duration-200 cursor-pointer relative"
          @click="showMetricTooltip('ventas')"
        >
          <div class="flex items-center justify-between">
            <div>
              <p class="text-sm text-blue-600 font-medium">Ventas Semana</p>
              <p class="text-xl font-bold text-blue-800">${{ metrics.ventasSemana }}</p>
            </div>
            <TrendingUp class="w-8 h-8 text-blue-600" />
          </div>
          <div v-if="showTooltip === 'ventas'" class="absolute top-0 left-0 right-0 bg-blue-800 text-white p-2 rounded-t-xl text-xs">
            {{ getTooltipContent('ventas') }}
          </div>
        </div>

        <!-- Panel de Alertas -->
        <div
          class="p-4 border rounded-xl bg-gradient-to-br from-red-50 to-red-100 hover:shadow-lg transition-all duration-200 cursor-pointer relative"
          @click="showMetricTooltip('alertas')"
        >
          <div class="flex items-center justify-between">
            <div>
              <p class="text-sm text-red-600 font-medium">Alertas de Stock</p>
              <p class="text-xl font-bold text-red-800">{{ alertasStock.length }}</p>
            </div>
            <AlertTriangle class="w-8 h-8 text-red-600" />
          </div>
          <div v-if="showTooltip === 'alertas'" class="absolute top-0 left-0 right-0 bg-red-800 text-white p-2 rounded-t-xl text-xs">
            {{ getTooltipContent('alertas') }}
          </div>
        </div>

        <!-- Producto en Tendencia -->
        <div
          class="p-4 border rounded-xl bg-gradient-to-br from-purple-50 to-purple-100 hover:shadow-lg transition-all duration-200 cursor-pointer relative"
          @click="showMetricTooltip('tendencia')"
        >
          <div class="flex items-center justify-between">
            <div>
              <p class="text-sm text-purple-600 font-medium">Producto en Tendencia</p>
              <p class="text-xl font-bold text-purple-800">{{ productoTendencia.nombre }}</p>
              <p class="text-xs text-purple-600">+{{ productoTendencia.crecimiento }}%</p>
            </div>
            <TrendingUp class="w-8 h-8 text-purple-600" />
          </div>
          <div v-if="showTooltip === 'tendencia'" class="absolute top-0 left-0 right-0 bg-purple-800 text-white p-2 rounded-t-xl text-xs">
            {{ getTooltipContent('tendencia') }}
          </div>
        </div>
      </div>

      <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <!-- Gráfico Predicción General -->
        <div class="p-4 bg-white rounded-xl border shadow-sm">
          <h3 class="text-lg font-semibold mb-2 flex items-center gap-2">
            <TrendingUp class="w-5 h-5 text-blue-600" />
            Predicción General (7 días)
          </h3>
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
                      tension: 0.4,
                      fill: true
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
            <AlertTriangle class="w-12 h-12 mx-auto mb-2 text-gray-400" />
            <p>No hay datos de predicción general disponibles</p>
          </div>
        </div>

        <!-- Ventas diarias -->
        <div class="p-4 bg-white rounded-xl border shadow-sm">
          <h3 class="text-lg font-semibold mb-2 flex items-center gap-2">
            <Calendar class="w-5 h-5 text-green-600" />
            Tendencia Diaria (90 días)
          </h3>
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
            <AlertTriangle class="w-12 h-12 mx-auto mb-2 text-gray-400" />
            <p>No hay datos de ventas diarias disponibles</p>
          </div>
        </div>
      </div>

      <!-- Predicción por Producto con Segmentación -->
      <div class="p-4 bg-white border rounded-xl shadow-sm">
        <div class="flex justify-between items-center mb-4">
          <h3 class="text-lg font-semibold flex items-center gap-2">
            <Brain class="w-5 h-5 text-purple-600" />
            Predicción por Producto (Segmentación Inteligente)
          </h3>
          <!-- Selector de Categoría -->
          <div class="flex gap-2">
            <button
              v-for="cat in ['A', 'B', 'C']"
              :key="cat"
              @click="selectedCategory = cat"
              :class="[
                'px-3 py-1 rounded-md text-sm font-medium transition-all duration-200',
                selectedCategory === cat
                  ? 'bg-blue-600 text-white'
                  : 'bg-gray-200 text-gray-700 hover:bg-gray-300'
              ]"
            >
              Categoría {{ cat }}
              <span class="text-xs ml-1">
                {{ cat === 'A' ? '(Top)' : cat === 'B' ? '(Medio)' : '(Bajo)' }}
              </span>
            </button>
          </div>
        </div>

        <div v-if="Object.keys(productosFiltered).length === 0" class="text-center text-gray-500 py-4">
          <AlertTriangle class="w-8 h-8 mx-auto mb-2 text-gray-400" />
          <p>No hay productos en la categoría {{ selectedCategory }} disponibles</p>
        </div>
        <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
          <div v-for="(productos, id) in productosFiltered" :key="id" class="p-3 border rounded-lg bg-gradient-to-br from-gray-50 to-gray-100 hover:shadow-md transition-all duration-200">
            <div v-for="producto in productos" :key="producto.id" class="mb-2 last:mb-0">
              <h4 class="font-medium text-gray-800">{{ producto.nombre }}</h4>
              <div class="text-sm text-gray-600">
                <p class="flex items-center gap-1">
                  <TrendingUp class="w-3 h-3" />
                  Próxima semana: <span class="font-bold text-blue-600">{{ Math.round(producto.prediccion) }}</span> unidades
                </p>
                <p class="text-xs">
                  <span :class="[
                    'px-2 py-1 rounded-full text-xs font-medium',
                    producto.categoria === 'A' ? 'bg-green-100 text-green-800' :
                    producto.categoria === 'B' ? 'bg-yellow-100 text-yellow-800' :
                    'bg-gray-100 text-gray-800'
                  ]">
                    Categoría {{ producto.categoria }}
                  </span>
                </p>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Top Productos del Mes Anterior -->
      <div class="p-4 bg-white border rounded-xl shadow-sm">
        <h3 class="text-lg font-semibold mb-4 flex items-center gap-2">
          <Award class="w-5 h-5 text-yellow-600" />
          Top Productos ({{ mesAnterior }})
        </h3>
        <div v-if="topProductos.length === 0" class="text-center text-gray-500 py-4">
          <AlertTriangle class="w-8 h-8 mx-auto mb-2 text-gray-400" />
          <p>No hay datos de ventas de productos disponibles</p>
        </div>
        <div v-else class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div v-for="(item, i) in topProductos" :key="i" class="flex items-center justify-between p-3 border rounded-lg hover:shadow-md transition-all duration-200 hover:scale-102">
            <div class="flex items-center gap-3">
              <div :class="[
                'w-8 h-8 rounded-full flex items-center justify-center text-sm font-bold',
                i === 0 ? 'bg-yellow-500 text-white' :
                i === 1 ? 'bg-gray-400 text-white' :
                i === 2 ? 'bg-amber-600 text-white' :
                'bg-blue-100 text-blue-800'
              ]">
                {{ i + 1 }}
              </div>
              <div>
                <p class="font-medium">{{ item.nombre }}</p>
                <p class="text-sm text-gray-500">{{ item.total_vendido }} unidades vendidas</p>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Productos Estacionales -->
      <div class="p-4 bg-white border rounded-xl shadow-sm">
        <h3 class="text-lg font-semibold mb-2 flex items-center gap-2">
          <Calendar class="w-5 h-5 text-orange-600" />
          Productos Estacionales
        </h3>
        <div v-if="productosEstacionales.length === 0" class="text-center text-gray-500 py-4">
          <AlertTriangle class="w-8 h-8 mx-auto mb-2 text-gray-400" />
          <p>No hay alertas estacionales disponibles</p>
        </div>
        <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
          <div v-for="(item, i) in productosEstacionales" :key="i" class="p-3 border rounded-lg bg-gradient-to-br from-orange-50 to-orange-100">
            <h4 class="font-medium text-orange-800">{{ item.nombre }}</h4>
            <p class="text-sm text-orange-600">Temporada: {{ item.temporada }}</p>
            <p class="text-xs text-orange-500">{{ item.alerta }}</p>
          </div>
        </div>
      </div>

      <!-- Alertas de Stock -->
      <div class="p-4 bg-white border rounded-xl shadow-sm">
        <h3 class="text-lg font-semibold mb-2 flex items-center gap-2">
          <AlertTriangle class="w-5 h-5 text-red-600" />
          Alertas de Stock Crítico
        </h3>
        <div v-if="alertasStock.length === 0" class="text-center text-gray-500 py-4">
          <Info class="w-8 h-8 mx-auto mb-2 text-green-400" />
          <p class="text-green-600">¡Todos los productos tienen stock suficiente!</p>
        </div>
        <div v-else class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div v-for="(item, i) in alertasStock" :key="i" class="flex items-center justify-between p-3 border-l-4 border-red-500 bg-red-50 rounded-lg">
            <div>
              <p class="font-medium text-red-800">{{ item.nombre }}</p>
              <p class="text-sm text-red-600">Stock: {{ item.stock_actual }}/{{ item.stock_minimo }}</p>
              <p class="text-xs text-red-500">{{ item.dias_restantes }} días restantes</p>
            </div>
            <AlertTriangle class="w-6 h-6 text-red-600" />
          </div>
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
