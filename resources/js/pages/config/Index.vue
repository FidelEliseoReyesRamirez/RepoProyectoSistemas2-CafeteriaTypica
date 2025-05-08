<script setup lang="ts">
import { ref, watch } from 'vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, usePage } from '@inertiajs/vue3';
import PlaceholderPattern from '@/components/PlaceholderPattern.vue';

const page = usePage() as any;
const config = page.props.config;

const tiempoCancelacion = ref<number>(config?.tiempo_cancelacion_minutos ?? 5);
const tiempoEdicion = ref<number>(config?.tiempo_edicion_minutos ?? 10);
const estadosCancelables = ref<string[]>(config?.estados_cancelables ?? []);
const estadosEditables = ref<string[]>(config?.estados_editables ?? []);
const todosLosEstados = ref<string[]>([...new Set(config?.todos_los_estados as string[] ?? [])]);

const sinLimite = ref<boolean>(tiempoCancelacion.value === 0);
watch(sinLimite, (val) => {
  tiempoCancelacion.value = val ? 0 : 5;
});

const validarNumero = (event: KeyboardEvent) => {
  const allowed = /[0-9]/;
  if (!allowed.test(event.key)) {
    event.preventDefault();
  }
};

const guardarConfig = async () => {
  if (!Number.isInteger(tiempoCancelacion.value) || tiempoCancelacion.value < 1) {
    if (!sinLimite.value) {
      alert("Debes ingresar un número válido o marcar 'Sin límite'.");
      return;
    }
  }

  await fetch('/config/tiempo-cancelacion', {
    method: 'PUT',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ minutos: tiempoCancelacion.value }),
  });

  await fetch('/config/tiempo-edicion', {
    method: 'PUT',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ minutos: tiempoEdicion.value }),
  });

  await fetch('/config/estados', {
    method: 'PUT',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ tipo: 'cancelables', estados: estadosCancelables.value }),
  });

  await fetch('/config/estados', {
    method: 'PUT',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ tipo: 'editables', estados: estadosEditables.value }),
  });

  alert('Configuración guardada.');
};
</script>

<template>
  <AppLayout>
    <Head title="Configuración del sistema" />

    <div class="p-4 flex flex-col gap-4 text-[#4b3621] dark:text-white">
      <div class="flex flex-col gap-4 rounded-xl p-4 border border-[#c5a880] dark:border-[#8c5c3b]">
        <div class="grid gap-4 md:grid-cols-2">
          <div>
            <label class="block text-sm font-medium mb-1">Minutos para cancelar pedidos</label>
            <input
              type="number"
              v-model.number="tiempoCancelacion"
              :disabled="sinLimite"
              min="0"
              class="w-full rounded border px-3 py-2 text-sm dark:bg-[#2c211b] dark:text-white border-[#c5a880] dark:border-[#8c5c3b]"
              @keypress="validarNumero"
            />
            <div class="mt-2">
              <label class="inline-flex items-center gap-2 text-sm">
                <input type="checkbox" v-model="sinLimite" @change="guardarConfig" />
                Sin límite
              </label>
            </div>
          </div>

          <div>
            <label class="block text-sm font-medium mb-1">Minutos para editar pedidos</label>
            <input
              type="number"
              v-model.number="tiempoEdicion"
              min="0"
              class="w-full rounded border px-3 py-2 text-sm dark:bg-[#2c211b] dark:text-white border-[#c5a880] dark:border-[#8c5c3b]"
              @keypress="validarNumero"
            />
          </div>
        </div>

        <div class="grid gap-4 md:grid-cols-2">
          <div>
            <label class="block text-sm font-semibold mb-2">Estados que permiten cancelar</label>
            <div class="space-y-1 p-2 border rounded dark:bg-[#1d1b16] border-[#c5a880] dark:border-[#8c5c3b]">
              <div
                v-for="estado in todosLosEstados"
                :key="'cancel-' + estado"
                class="flex items-center gap-2"
              >
                <input
                  type="checkbox"
                  :value="estado"
                  v-model="estadosCancelables"
                  class="form-checkbox h-4 w-4 text-green-600 border-[#c5a880] dark:border-[#8c5c3b]"
                />
                <span class="text-sm">{{ estado }}</span>
              </div>
            </div>
          </div>

          <div>
            <label class="block text-sm font-semibold mb-2">Estados que permiten editar</label>
            <div class="space-y-1 p-2 border rounded dark:bg-[#1d1b16] border-[#c5a880] dark:border-[#8c5c3b]">
              <div
                v-for="estado in todosLosEstados"
                :key="'edit-' + estado"
                class="flex items-center gap-2"
              >
                <input
                  type="checkbox"
                  :value="estado"
                  v-model="estadosEditables"
                  class="form-checkbox h-4 w-4 text-blue-600 border-[#c5a880] dark:border-[#8c5c3b]"
                />
                <span class="text-sm">{{ estado }}</span>
              </div>
            </div>
          </div>
        </div>

        <div class="text-center">
          <button
            @click="guardarConfig"
            class="bg-green-600 hover:bg-green-700 text-white px-6 py-2 rounded shadow text-sm"
          >
            Guardar configuración
          </button>
        </div>
      </div>

      <div class="grid auto-rows-min gap-4 md:grid-cols-3">
        <div
          class="relative aspect-video overflow-hidden rounded-xl border border-sidebar-border/70 dark:border-sidebar-border"
        >
          <PlaceholderPattern />
        </div>
        <div
          class="relative aspect-video overflow-hidden rounded-xl border border-sidebar-border/70 dark:border-sidebar-border"
        >
          <PlaceholderPattern />
        </div>
        <div
          class="relative aspect-video overflow-hidden rounded-xl border border-sidebar-border/70 dark:border-sidebar-border"
        >
          <PlaceholderPattern />
        </div>
      </div>
      <div
        class="relative min-h-[100vh] flex-1 rounded-xl border border-sidebar-border/70 dark:border-sidebar-border md:min-h-min"
      >
        <PlaceholderPattern />
      </div>
    </div>
  </AppLayout>
</template>