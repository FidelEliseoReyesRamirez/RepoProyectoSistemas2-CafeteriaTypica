<script setup lang="ts">
import { ref, computed } from 'vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { usePage } from '@inertiajs/vue3';
import type { BreadcrumbItem } from '@/types';
import axios from 'axios';
import { RefreshCw, Brain, Zap } from 'lucide-vue-next';

interface Metrics {
  ventasSemana?: number;
  clientesAtendidos?: number;
  ticketPromedio?: number;
}

// Datos con valores por defecto seguros
const pageProps = computed(() => {
  const props = (usePage().props as unknown) as {
    forecast?: any[];
    topProduct?: any;
    metrics?: Metrics;
    comboSugerido?: any[];
    ventasDiarias?: any[];
    forecastPorProducto?: Record<string, { ds: string; yhat: number }[]>;
  };

  return {
    forecast: props.forecast || [],
    topProduct: props.topProduct || { nombre: 'N/A', cantidad: 0 },
    metrics: props.metrics || { ventasSemana: 0, clientesAtendidos: 0, ticketPromedio: 0 },
    comboSugerido: props.comboSugerido || [],
    ventasDiarias: props.ventasDiarias || [],
    forecastPorProducto: props.forecastPorProducto || {}
  };
});

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

defineProps<{ breadcrumbs?: BreadcrumbItem[] }>();
</script>

<template>
  <AppLayout>
    <div class="p-6 space-y-8">
      <div class="flex justify-between items-center">
        <div>
          <h1 class="text-3xl font-bold">Dashboard de Ventas</h1>
          <p class="text-gray-500 text-sm">Actualizado a las {{ lastUpdated.toLocaleTimeString() }}</p>
        </div>
        <button
          :disabled="isGenerating"
          @click="generarPrediccion"
          class="flex items-center gap-2 px-4 py-2 rounded-lg text-white bg-blue-600 hover:bg-blue-700 disabled:bg-gray-300"
        >
          <component :is="isGenerating ? RefreshCw : Brain" class="w-4 h-4" :class="{ 'animate-spin': isGenerating }" />
          <span>{{ isGenerating ? 'Generando...' : 'Generar Predicción' }}</span>
          <Zap class="w-4 h-4" />
        </button>
      </div>

      <!-- Sección de métricas con protección contra undefined -->
      <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <div class="p-4 border rounded-xl bg-white">
          <p class="text-gray-500 text-sm">Ventas Semana</p>
          <p class="text-xl font-bold">${{ pageProps.metrics.ventasSemana }}</p>
        </div>
        <div class="p-4 border rounded-xl bg-white">
          <p class="text-gray-500 text-sm">Clientes Atendidos</p>
          <p class="text-xl font-bold">{{ pageProps.metrics.clientesAtendidos }}</p>
        </div>
        <div class="p-4 border rounded-xl bg-white">
          <p class="text-gray-500 text-sm">Ticket Promedio</p>
          <p class="text-xl font-bold">${{ pageProps.metrics.ticketPromedio }}</p>
        </div>
        <div class="p-4 border rounded-xl bg-white">
          <p class="text-gray-500 text-sm">Producto Top</p>
          <p class="text-xl font-bold">{{ pageProps.topProduct.nombre }} ({{ pageProps.topProduct.cantidad }} ventas)</p>
        </div>
      </div>

      <!-- Gráficos con protección contra arrays vacíos -->
      <div class="grid grid-cols-1 lg:grid-cols-2 gap-6" v-if="pageProps.forecast.length > 0 && pageProps.ventasDiarias.length > 0">
        <div class="p-4 border rounded-xl bg-white">
          <h3 class="text-lg font-semibold mb-2">Predicción de Demanda General</h3>
          <img
            :src="`https://quickchart.io/chart?c=${encodeURIComponent(JSON.stringify({
              type: 'line',
              data: {
                labels: pageProps.forecast.map(f => f.ds?.slice(0, 10) || ''),
                datasets: [
                  { label: 'Predicción', data: pageProps.forecast.map(f => f.yhat), fill: false, borderColor: '#3B82F6' },
                  { label: 'Real', data: pageProps.forecast.map(f => f.real || null), fill: false, borderColor: '#10B981' }
                ]
              }
            }))}`"
            alt="Predicción de demanda"
            class="rounded-xl w-full"
          />
        </div>

        <div class="p-4 border rounded-xl bg-white">
          <h3 class="text-lg font-semibold mb-2">Ventas por Día</h3>
          <img
            :src="`https://quickchart.io/chart?c=${encodeURIComponent(JSON.stringify({
              type: 'bar',
              data: {
                labels: pageProps.ventasDiarias.map(v => v.dia),
                datasets: [{ label: 'Ventas', data: pageProps.ventasDiarias.map(v => v.ventas), backgroundColor: '#10B981' }]
              }
            }))}`"
            alt="Ventas diarias"
            class="rounded-xl w-full"
          />
        </div>
      </div>
      <div v-else class="p-4 border rounded-xl bg-white text-center text-gray-500">
        Cargando datos de gráficos...
      </div>

      <!-- Predicción por Producto con protección -->
      <div class="p-4 border rounded-xl bg-white" v-if="Object.keys(pageProps.forecastPorProducto).length > 0">
        <h3 class="text-lg font-semibold mb-4">Predicción por Producto</h3>
        <div class="grid md:grid-cols-2 gap-4">
          <div
            v-for="(predicciones, productoId) in pageProps.forecastPorProducto"
            :key="productoId"
            class="border rounded-xl p-4"
          >
            <h4 class="font-semibold text-sm mb-2">Producto ID {{ productoId }}</h4>
            <img
              :src="`https://quickchart.io/chart?c=${encodeURIComponent(JSON.stringify({
                type: 'line',
                data: {
                  labels: predicciones.map(p => p.ds?.slice(0, 10) || ''),
                  datasets: [
                    {
                      label: 'Predicción',
                      data: predicciones.map(p => p.yhat),
                      fill: false,
                      borderColor: '#3B82F6'
                    }
                  ]
                }
              }))}`"
              alt="Predicción Producto"
              class="rounded w-full"
            />
          </div>
        </div>
      </div>

      <!-- Combo Sugerido con protección -->
      <div class="p-4 border rounded-xl bg-white" v-if="pageProps.comboSugerido.length > 0">
        <h3 class="text-lg font-semibold mb-4">Combo Sugerido</h3>
        <ul class="list-disc list-inside space-y-1">
          <li v-for="(item, i) in pageProps.comboSugerido" :key="i">
            {{ item.producto }} ({{ item.frecuencia }} veces)
          </li>
        </ul>
        <div class="mt-4 text-sm text-purple-700">💡 Crear este combo podría aumentar las ventas en un 23%</div>
      </div>
    </div>
  </AppLayout>
</template>
