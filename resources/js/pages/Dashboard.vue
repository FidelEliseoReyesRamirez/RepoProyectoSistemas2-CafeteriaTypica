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
</script>

<template>
  <AppLayout :breadcrumbs="props.breadcrumbs">
    <div class="flex h-full flex-1 flex-col gap-6 rounded-xl p-4">
      <h2 class="text-2xl font-bold tracking-tight">Resumen de Ventas</h2>

      <div class="rounded-xl border p-4 shadow-sm">
        <h3 class="mb-4 text-lg font-semibold">Ventas Totales</h3>
        <div class="mb-4 flex gap-2">
          <button
            class="rounded-lg border px-3 py-1 text-sm"
            :class="modo === 'semana' ? 'bg-primary text-white' : 'bg-muted'"
            @click="modo = 'semana'"
          >
            Semana
          </button>
          <button
            class="rounded-lg border px-3 py-1 text-sm"
            :class="modo === 'mes' ? 'bg-primary text-white' : 'bg-muted'"
            @click="modo = 'mes'"
          >
            Mes
          </button>
          <button
            class="rounded-lg border px-3 py-1 text-sm"
            :class="modo === 'año' ? 'bg-primary text-white' : 'bg-muted'"
            @click="modo = 'año'"
          >
            Año
          </button>
        </div>
        <p class="text-3xl font-bold text-primary">Bs. {{ valorVentas.toFixed(2) }}</p>
        <p class="text-sm text-muted mt-1">Clientes atendidos esta semana: {{ metrics.clientesAtendidos }}</p>
      </div>
    </div>
  </AppLayout>
</template>
