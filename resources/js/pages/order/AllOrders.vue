<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import { ref, onMounted, computed } from 'vue';
import type { PageProps } from '@/types';

const orders = ref<any[]>([]);
const config = ref({
    estados_cancelables: [] as string[],
    estados_editables: [] as string[],
    tiempos_por_estado: {} as Record<string, { cancelar: number; editar: number }>,
});
const serverNow = ref(Date.now());

const fetchOrders = async () => {
    try {
        const response = await fetch('/api/all-orders');
        const data = await response.json();
        orders.value = data.orders;
        serverNow.value = new Date(data.now).getTime();
        config.value = data.config;
    } catch (error) {
        console.error('Error al obtener los pedidos:', error);
    }
};

onMounted(fetchOrders);

const puedeCancelar = (orderDate: string, estado: string): boolean => {
    if (!config.value.estados_cancelables.includes(estado)) return false;

    const fechaPedido = new Date(orderDate).getTime();
    const limite = (config.value.tiempos_por_estado[estado]?.cancelar ?? 0) * 60 * 1000;
    const diferencia = serverNow.value - fechaPedido;

    return limite === 0 || diferencia <= limite;
};

const puedeEditar = (orderDate: string, estado: string): boolean => {
    if (!config.value.estados_editables.includes(estado)) return false;

    const fechaPedido = new Date(orderDate).getTime();
    const limite = (config.value.tiempos_por_estado[estado]?.editar ?? 0) * 60 * 1000;
    const diferencia = serverNow.value - fechaPedido;

    return limite === 0 || diferencia <= limite;
};
</script>

<template>
    <AppLayout>
        <Head title="All Orders" />

        <div class="p-4 sm:p-6 text-[#4b3621] dark:text-white">
            <h1 class="text-2xl font-bold mb-4 text-[#593E25] dark:text-[#d9a679]">Todos los pedidos</h1>

            <div v-if="orders.length">
                <div v-for="order in orders" :key="order.id_pedido"
                    class="mb-4 p-4 rounded shadow border border-[#d8c6ad] dark:border-[#6c4f3c] relative overflow-hidden"
                    :style="{ backgroundColor: order.estadopedido.color_codigo + '22' }">

                    <div class="absolute top-0 left-0 h-full w-2" :style="{ backgroundColor: order.estadopedido?.color_codigo || '#ccc' }"></div>
                    <div class="absolute top-0 right-0 h-full w-2" :style="{ backgroundColor: order.estadopedido?.color_codigo || '#ccc' }"></div>

                    <div class="text-sm text-black dark:text-white">
                        <p class="font-semibold">Pedido #{{ order.id_pedido }}</p>
                        <p class="text-xs">{{ new Date(order.fecha_hora_registro).toLocaleString() }}</p>
                        <div class="flex items-center gap-2 mt-1">
                            <span class="w-3 h-3 rounded-full" :style="{ backgroundColor: order.estadopedido.color_codigo }"></span>
                            <span class="px-2 py-0.5 rounded text-xs font-semibold"
                                :style="{ backgroundColor: order.estadopedido.color_codigo }">
                                {{ order.estadopedido.nombre_estado }}
                            </span>
                        </div>

                        <div class="flex flex-wrap gap-2 mt-3">
                            <button @click="router.visit(`/order/edit/${order.id_pedido}`)"
                                class="text-white text-xs px-3 py-1 rounded shadow"
                                :class="puedeEditar(order.fecha_hora_registro, order.estadopedido.nombre_estado)
                                    ? 'bg-yellow-600 hover:bg-yellow-700'
                                    : 'bg-gray-400 cursor-not-allowed'"
                                :disabled="!puedeEditar(order.fecha_hora_registro, order.estadopedido.nombre_estado)">
                                Editar
                            </button>

                            <button v-if="order.estadopedido.nombre_estado !== 'Cancelado'"
                                @click="router.put(`/order/${order.id_pedido}/cancelar`)"
                                :class="[
                                    'text-white text-xs px-3 py-1 rounded shadow',
                                    puedeCancelar(order.fecha_hora_registro, order.estadopedido.nombre_estado)
                                        ? 'bg-red-600 hover:bg-red-700'
                                        : 'bg-gray-400 cursor-not-allowed'
                                ]"
                                :disabled="!puedeCancelar(order.fecha_hora_registro, order.estadopedido.nombre_estado)">
                                Cancelar
                            </button>

                            <button v-if="order.estadopedido.nombre_estado === 'Cancelado'"
                                @click="router.put(`/order/${order.id_pedido}/rehacer`)"
                                class="bg-indigo-600 hover:bg-indigo-700 text-white text-xs px-3 py-1 rounded shadow">
                                Restaurar
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <p v-else class="text-sm text-gray-600">No hay pedidos registrados.</p>
        </div>
    </AppLayout>
</template>
