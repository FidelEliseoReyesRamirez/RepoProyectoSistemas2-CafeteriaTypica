<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head } from '@inertiajs/vue3';
import { onMounted, ref, computed } from 'vue';
import axios from 'axios';

const goBack = () => history.back();

const orders = ref<any[]>([]);

const filtroNumero = ref('');
const filtroTiempo = ref('');
const fechaInicio = ref<string | null>(null);
const fechaFin = ref<string | null>(null);
const isFechaFinalInvalid = ref(false);

const validarFechas = () => {
  if (fechaInicio.value && fechaFin.value) {
    isFechaFinalInvalid.value = new Date(fechaFin.value) < new Date(fechaInicio.value);
  } else {
    isFechaFinalInvalid.value = false;
  }
};

const filtrarPorTiempo = (fecha: string) => {
  const ahora = new Date();
  const fechaPedido = new Date(fecha);
  switch (filtroTiempo.value) {
    case 'ultima_hora': return ahora.getTime() - fechaPedido.getTime() <= 3600000;
    case 'ultimas_2': return ahora.getTime() - fechaPedido.getTime() <= 7200000;
    case 'hoy': return fechaPedido.toDateString() === ahora.toDateString();
    case 'ultimas_24': return ahora.getTime() - fechaPedido.getTime() <= 86400000;
    case 'ultimos_2_dias': return ahora.getTime() - fechaPedido.getTime() <= 2 * 86400000;
    case 'ultima_semana': return ahora.getTime() - fechaPedido.getTime() <= 7 * 86400000;
    case 'este_mes': return fechaPedido.getMonth() === ahora.getMonth() && fechaPedido.getFullYear() === ahora.getFullYear();
    case 'rango_fechas':
      if (!fechaInicio.value || !fechaFin.value || isFechaFinalInvalid.value) return true;
      const ini = new Date(fechaInicio.value).getTime();
      const fin = new Date(fechaFin.value).getTime();
      return fechaPedido.getTime() >= ini && fechaPedido.getTime() <= fin;
    default: return true;
  }
};

const loadOrders = async () => {
  const { data } = await axios.get('/api/all-orders');
  orders.value = data.orders;
};

onMounted(loadOrders);

const filteredOrders = computed(() =>
  orders.value.filter(p =>
    p.estadopedido.nombre_estado === 'Cancelado' &&
    (!filtroNumero.value || p.id_pedido.toString().includes(filtroNumero.value)) &&
    filtrarPorTiempo(p.fecha_hora_registro)
  )
);
</script>
<template>
  <AppLayout>

    <Head title="Pedidos Cancelados" />
    <div class="p-4 sm:p-6 w-full max-w-screen-xl mx-auto text-[#4b3621] dark:text-white">
      <h1 class="text-2xl sm:text-3xl font-bold mb-6">Pedidos Cancelados</h1>

      <div class="flex flex-wrap gap-2 mb-6 items-center">
        <button @click="goBack" class="px-3 py-1.5 text-sm rounded shadow text-white" style="background-color: #6b7280">
          Volver
        </button>
        <input v-model="filtroNumero" type="text" placeholder="Buscar por #"
          class="border text-black rounded px-3 py-1.5 text-sm w-44" />
        <select v-model="filtroTiempo" class="border text-black rounded px-3 py-1.5 text-sm w-44">
          <option value="">Todo el tiempo</option>
          <option value="ultima_hora">Última hora</option>
          <option value="ultimas_2">Últimas 2 horas</option>
          <option value="hoy">Hoy</option>
          <option value="ultimas_24">Últimas 24 horas</option>
          <option value="ultimos_2_dias">Últimos 2 días</option>
          <option value="ultima_semana">Última semana</option>
          <option value="este_mes">Este mes</option>
          <option value="rango_fechas">Rango de fechas</option>
        </select>

        <div v-if="filtroTiempo === 'rango_fechas'" class="flex gap-2">
          <input type="date" v-model="fechaInicio" @change="validarFechas"
            class="border text-black rounded px-3 py-1.5 text-sm" />
          <input type="date" v-model="fechaFin" :min="fechaInicio || undefined" @change="validarFechas"
            class="border text-black rounded px-3 py-1.5 text-sm" />
        </div>

        <button @click="() => { filtroNumero = ''; filtroTiempo = ''; fechaInicio = null; fechaFin = null }"
          class="text-sm bg-red-500 hover:bg-red-600 text-white rounded px-3 py-1.5">
          Limpiar filtros
        </button>
      </div>

      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
        <div v-for="pedido in filteredOrders" :key="pedido.id_pedido"
          class="rounded-lg shadow border relative overflow-hidden border-red-500 p-4"
          :style="{ backgroundColor: `${pedido.estadopedido.color_codigo}22` }">
          <div class="absolute left-0 top-0 h-full w-2 bg-red-500"></div>
          <div class="absolute right-0 top-0 h-full w-2 bg-red-500"></div>

          <div class="flex justify-between items-center mb-2">
            <h2 class="font-bold">Pedido #{{ pedido.id_pedido }}</h2>
            <span class="text-xs">{{ new Date(pedido.fecha_hora_registro).toLocaleString('es-BO') }}</span>
          </div>

          <p class="text-xs font-semibold mb-2">
            Estado:
            <span class="rounded px-2 py-0.5 bg-red-500 text-white">{{ pedido.estadopedido.nombre_estado }}</span>
          </p>

          <ul class="text-sm divide-y divide-gray-300 dark:divide-gray-600">
            <li v-for="detalle in pedido.detallepedidos" :key="detalle.id_detalle" class="py-1">
              {{ detalle.cantidad }} x {{ detalle.producto.nombre }}
            </li>
          </ul>
        </div>
      </div>
    </div>
  </AppLayout>
</template>
