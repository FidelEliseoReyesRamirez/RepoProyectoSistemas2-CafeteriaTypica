<script setup lang="ts">
import { ref, onMounted, watch } from 'vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, usePage } from '@inertiajs/vue3';
import PlaceholderPattern from '../components/PlaceholderPattern.vue';
import type { BreadcrumbItem } from '@/types';

defineProps<{ breadcrumbs?: BreadcrumbItem[] }>();

const page = usePage() as any;
const user = page.props.auth?.user;

// Inicializa con valor recibido o 5
const tiempoCancelacion = ref<number>(page.props.config?.tiempo_cancelacion_minutos ?? 5);
const sinLimite = ref<boolean>(tiempoCancelacion.value === 0);

// Watch para el checkbox
watch(sinLimite, (val) => {
  tiempoCancelacion.value = val ? 0 : 5;
});

// Corrige valores no válidos al escribir
const onInput = () => {
  if (tiempoCancelacion.value > 1000) {
    sinLimite.value = true;
  } else {
    if (tiempoCancelacion.value < 1 && !sinLimite.value) {
      tiempoCancelacion.value = 1;
    }
  }
};


// Guarda config
const guardarTiempo = async () => {
  if (!Number.isInteger(tiempoCancelacion.value) || tiempoCancelacion.value < 1) {
    if (!sinLimite.value) {
      alert("Debes ingresar un número entero mayor a 0 o marcar 'Sin límite'.");
      return;
    }
  }

  try {
    await fetch('/config/tiempo-cancelacion', {
      method: 'PUT',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ minutos: tiempoCancelacion.value }),
    });
  } catch (err) {
    console.error('Error al guardar el tiempo de cancelación:', err);
  }
};
</script>

<template>
  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="p-4 flex items-center gap-2">
      <label class="text-sm font-medium whitespace-nowrap">Minutos para cancelar pedidos: (Meseros)</label>
      <input
        type="number"
        v-model.number="tiempoCancelacion"
        :disabled="sinLimite"
        @input="onInput"
        @change="guardarTiempo"
        min="1"
        step="1"
        class="w-20 rounded border-gray-300 dark:border-gray-600 bg-white dark:bg-[#2c211b] text-black dark:text-white text-sm p-1"
      />
      <label class="flex items-center gap-1 text-sm">
        <input type="checkbox" v-model="sinLimite" @change="guardarTiempo" />
        Sin límite
      </label>
    </div>

    <div class="flex h-full flex-1 flex-col gap-4 rounded-xl p-4">
      <div class="grid auto-rows-min gap-4 md:grid-cols-3">
        <div class="relative aspect-video overflow-hidden rounded-xl border border-sidebar-border/70 dark:border-sidebar-border">
          <PlaceholderPattern />
        </div>
        <div class="relative aspect-video overflow-hidden rounded-xl border border-sidebar-border/70 dark:border-sidebar-border">
          <PlaceholderPattern />
        </div>
        <div class="relative aspect-video overflow-hidden rounded-xl border border-sidebar-border/70 dark:border-sidebar-border">
          <PlaceholderPattern />
        </div>
      </div>
      <div class="relative min-h-[100vh] flex-1 rounded-xl border border-sidebar-border/70 dark:border-sidebar-border md:min-h-min">
        <PlaceholderPattern />
      </div>
    </div>
  </AppLayout>
</template>
