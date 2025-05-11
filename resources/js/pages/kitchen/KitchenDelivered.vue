<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head } from '@inertiajs/vue3';
import { onMounted, ref, computed } from 'vue';
import axios from 'axios';

const orders = ref<any[]>([]);

// Cargar pedidos desde la API
const loadOrders = async () => {
  const { data } = await axios.get('/api/all-orders');
  orders.value = data.orders;
};

// Cambiar el estado de un pedido a "Pendiente"
const changeStatusToPending = async (orderId: number) => {
  await axios.put(`/api/kitchen-orders/${orderId}/estado`, { estado: 'Pendiente' });
  loadOrders(); // Recargar los pedidos después de actualizar el estado
};

// Función para ir atrás en el historial
const goBack = () => history.back();

onMounted(loadOrders);

// Filtrar los pedidos con estado "Entregado"
const filteredOrders = computed(() =>
  orders.value.filter(p => p.estadopedido.nombre_estado === 'Entregado')
);
</script>

<template>
  <AppLayout>
    <Head title="Delivered Orders" />
    <div class="p-4 sm:p-6 w-full max-w-screen-xl mx-auto text-[#4b3621] dark:text-white">
      <h1 class="text-2xl sm:text-3xl font-bold mb-6">Pedidos Entregados</h1>

      <!-- Botón Volver -->
      <button @click="goBack" class="mb-6 px-4 py-2 text-sm rounded shadow text-white" style="background-color: #6b7280">
        Volver
      </button>

      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
        <!-- Mostrar pedidos filtrados -->
        <div v-for="pedido in filteredOrders" :key="pedido.id_pedido"
             class="rounded-lg shadow border relative overflow-hidden p-4"
             :style="{ backgroundColor: `${pedido.estadopedido.color_codigo}22`, borderColor: pedido.estadopedido.color_codigo }">

          <div class="absolute left-0 top-0 h-full w-2"
               :style="{ backgroundColor: pedido.estadopedido.color_codigo }"></div>
          <div class="absolute right-0 top-0 h-full w-2"
               :style="{ backgroundColor: pedido.estadopedido.color_codigo }"></div>

          <div class="flex justify-between items-center mb-2">
            <h2 class="font-bold">Pedido #{{ pedido.id_pedido }}</h2>
            <span class="text-xs">
              {{ new Date(pedido.fecha_hora_registro).toLocaleString('es-BO') }}
            </span>
          </div>

          <p class="text-xs font-semibold mb-2">
            Estado:
            <span class="rounded px-2 py-0.5 text-white"
                  :style="{ backgroundColor: pedido.estadopedido.color_codigo }">
              {{ pedido.estadopedido.nombre_estado }}
            </span>
          </p>

          <ul class="text-sm divide-y divide-gray-300 dark:divide-gray-600">
            <li v-for="detalle in pedido.detallepedidos" :key="detalle.id_detalle" class="py-1">
              {{ detalle.cantidad }} x {{ detalle.producto.nombre }}
              <span v-if="detalle.comentario" class="block text-xs italic">
                {{ detalle.comentario }}
              </span>
            </li>
          </ul>

          <!-- Botón para poner el pedido como pendiente -->
          <div class="mt-4 flex justify-center gap-2">
            <button @click="changeStatusToPending(pedido.id_pedido)"
                    class="text-xs bg-gray-600 hover:bg-gray-700 text-white rounded px-2 py-1">
              Poner como Pendiente
            </button>
          </div>
        </div>
      </div>
    </div>
  </AppLayout>
</template>
