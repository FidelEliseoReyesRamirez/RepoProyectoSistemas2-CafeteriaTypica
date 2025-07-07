<script setup lang="ts">
import { ref, computed } from 'vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, usePage } from '@inertiajs/vue3';
import type { BreadcrumbItem } from '@/types';

const props = defineProps<{ breadcrumbs?: BreadcrumbItem[] }>();
const page = usePage();

const metrics = page.props.metrics as {
  ventasSemana: number;
  ventasMes: number;
  ventasAño: number;
  clientesAtendidos: number;
};

const modo = ref<'semana' | 'mes' | 'año'>('semana');

const valorVentas = computed(() => {
  if (modo.value === 'semana') return metrics.ventasSemana;
  if (modo.value === 'mes') return metrics.ventasMes;
  return metrics.ventasAño;
});

const modoTexto = computed(() => {
  return modo.value === 'semana' ? 'semana' : modo.value === 'mes' ? 'mes' : 'año';
});
</script>

<template>
  <AppLayout :breadcrumbs="props.breadcrumbs">
    <div class="flex h-full flex-1 flex-col gap-6 rounded-xl p-4">
      <h2 class="text-2xl font-bold tracking-tight">Resumen de Ventas</h2>

      <div class="rounded-xl border p-4 shadow-sm">
        <h3 class="mb-4 text-lg font-semibold">Ventas Totales</h3>

        <!-- Hacer que el div sea más visible y responsivo -->
        <div class="mb-4 flex flex-wrap gap-2">
          <div
            class="flex-1 cursor-pointer p-3 rounded-lg border text-center hover:shadow transition-all duration-150"
            :class="modo === 'semana' ? 'bg-primary text-white font-semibold' : 'bg-muted'"
            @click="modo = 'semana'"
          >
            Semana
          </div>
          <div
            class="flex-1 cursor-pointer p-3 rounded-lg border text-center hover:shadow transition-all duration-150"
            :class="modo === 'mes' ? 'bg-primary text-white font-semibold' : 'bg-muted'"
            @click="modo = 'mes'"
          >
            Mes
          </div>
          <div
            class="flex-1 cursor-pointer p-3 rounded-lg border text-center hover:shadow transition-all duration-150"
            :class="modo === 'año' ? 'bg-primary text-white font-semibold' : 'bg-muted'"
            @click="modo = 'año'"
          >
            Año
          </div>
        </div>

        <p class="text-3xl font-bold text-primary">Bs. {{ valorVentas.toFixed(2) }}</p>
        <p class="text-sm text-muted mt-1">Clientes atendidos esta {{ modoTexto }}</p>
      </div>
    </div>
  </AppLayout>
</template>
