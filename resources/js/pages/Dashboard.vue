<script setup lang="ts">
import { ref, onMounted, watch } from 'vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, usePage } from '@inertiajs/vue3';
import PlaceholderPattern from '../components/PlaceholderPattern.vue';
import type { BreadcrumbItem } from '@/types';

const demandChart = ref(null);
const suggestions = ref([
  { id: 1, combo: 'Combo Café + Croissant', motivo: 'Alta demanda los lunes a las 8:00' },
  { id: 2, combo: 'Promo Té + Tarta', motivo: 'Consumo recurrente en tardes de viernes' },
]);

defineProps<{ breadcrumbs?: BreadcrumbItem[] }>();
</script>

<template>
  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="flex h-full flex-1 flex-col gap-6 rounded-xl p-4">
      <!-- Título -->
      <h2 class="text-2xl font-bold tracking-tight">Predicción de Demanda y Sugerencias</h2>

      <!-- Paneles con predicciones -->
      <div class="grid gap-4 md:grid-cols-3">
        <!-- Gráfico de demanda simulada -->
        <div class="rounded-xl border p-4 shadow-sm">
          <h3 class="mb-2 text-lg font-semibold">Demanda Estimada</h3>
          <img src="https://quickchart.io/chart?c={type:'bar',data:{labels:['Café','Tostadas','Empanadas','Jugos'],datasets:[{label:'Demanda semanal',data:[120,95,80,60]}]}}" alt="Gráfico de demanda" class="rounded" />
        </div>

        <!-- Producto más pedido -->
        <div class="rounded-xl border p-4 shadow-sm">
          <h3 class="mb-2 text-lg font-semibold">Producto Más Pedido</h3>
          <p class="text-xl font-bold text-primary">Café Americano</p>
          <p class="text-sm text-muted">120 unidades vendidas esta semana</p>
        </div>

        <!-- Combo sugerido -->
        <div class="rounded-xl border p-4 shadow-sm">
          <h3 class="mb-2 text-lg font-semibold">Combo Sugerido</h3>
          <ul class="list-disc list-inside text-sm">
            <li>1 Café Americano</li>
            <li>1 Empanada de Queso</li>
            <li>1 Jugo de Naranja</li>
          </ul>
          <p class="mt-2 text-muted text-xs">Sugerido automáticamente según los patrones de consumo.</p>
        </div>
      </div>

      <!-- Reporte gráfico adicional -->
      <div class="mt-6 rounded-xl border p-4 shadow-sm">
        <h3 class="mb-4 text-lg font-semibold">Tendencia de Consumo Diario</h3>
        <img src="https://quickchart.io/chart?c={type:'line',data:{labels:['Lun','Mar','Mié','Jue','Vie','Sáb','Dom'],datasets:[{label:'Pedidos por día',data:[50,65,80,75,90,120,100]}]}}" alt="Tendencia diaria" class="rounded" />
      </div>
    </div>
  </AppLayout>
</template>
