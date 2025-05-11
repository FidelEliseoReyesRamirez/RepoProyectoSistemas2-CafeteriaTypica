<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head } from '@inertiajs/vue3';
import { onMounted, ref, computed } from 'vue';
import axios from 'axios';


const goBack = () => history.back();

const orders = ref<any[]>([]);

// Cargar pedidos desde la API
const loadOrders = async () => {
  const { data } = await axios.get('/api/all-orders');
  orders.value = data.orders;
};

onMounted(loadOrders);

// Filtrar los pedidos con estado "Cancelado"
const filteredOrders = computed(() =>
  orders.value.filter(p => p.estadopedido.nombre_estado === 'Cancelado')
);


</script>

<template>
  <AppLayout>
    <Head title="Canceled Orders" />
    <div class="p-4 sm:p-6 w-full max-w-screen-xl mx-auto text-[#4b3621] dark:text-white">
      <h1 class="text-2xl sm:text-3xl font-bold mb-6">Pedidos Cancelados</h1>

  <!-- Botón Volver -->
      <button @click="goBack" class="mb-6 px-4 py-2 text-sm rounded shadow text-white" style="background-color: #6b7280">
        Volver
      </button>

      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
        <div v-for="pedido in filteredOrders" :key="pedido.id_pedido"
             class="rounded-lg shadow border relative overflow-hidden border-red-500 p-4"
             :style="{ backgroundColor: `${pedido.estadopedido.color_codigo}22` }">
          <div class="absolute left-0 top-0 h-full w-2 bg-red-500"></div>
          <div class="absolute right-0 top-0 h-full w-2 bg-red-500"></div>

          <div class="flex justify-between items-center mb-2">
            <h2 class="font-bold">Pedido #{{ pedido.id_pedido }}</h2>
            <span class="text-xs">
              {{ new Date(pedido.fecha_hora_registro).toLocaleString('es-BO') }}
            </span>
          </div>

          <p class="text-xs font-semibold mb-2">
            Estado:
            <span class="rounded px-2 py-0.5 bg-red-500 text-white">
              {{ pedido.estadopedido.nombre_estado }}
            </span>
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
