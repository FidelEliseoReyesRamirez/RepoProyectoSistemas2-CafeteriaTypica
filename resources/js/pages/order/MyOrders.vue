<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, router, usePage } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import type { PageProps } from '@/types';

const page = usePage<PageProps>();
const authUser = computed(() => page.props.auth?.user ?? null);

const props = defineProps<{
    orders: {
        id_pedido: number;
        fecha_hora_registro: string;
        estadopedido: {
            nombre_estado: string;
            color_codigo: string;
        };
        detallepedidos: {
            id_producto: number;
            cantidad: number;
            comentario: string;
            producto: {
                nombre: string;
                precio: number;
            };
        }[];
    }[];
}>();

const selectedOrder = ref<number | null>(null);
const showResumen = ref(false);

const abrirResumen = (id: number) => {
    selectedOrder.value = id;
    showResumen.value = true;
};

const cerrarResumen = () => {
    selectedOrder.value = null;
    showResumen.value = false;
};

const pedidoSeleccionado = computed(() => {
    return props.orders.find(o => o.id_pedido === selectedOrder.value);
});

const puedeCancelar = (orderDate: string): boolean => {
    if (!authUser.value) return false;
    const rol = authUser.value.id_rol;
    if (rol === 1) return true; // Admin
    if (rol === 2) {
        const fechaPedido = new Date(orderDate).getTime();
        const ahora = new Date().getTime();
        return (ahora - fechaPedido) < 5 * 60 * 1000; // 5 minutos
    }
    return false;
};
</script>

<template>
    <AppLayout>
        <Head title="My Orders" />

        <div class="p-4 sm:p-6 text-[#4b3621] dark:text-white">
            <div class="flex justify-between items-center mb-4">
                <h1 class="text-2xl font-bold text-[#593E25] dark:text-[#d9a679]">Mis Pedidos</h1>
                <button @click="() => router.visit('/order')"
                    class="bg-green-600 hover:bg-green-700 text-white text-xs sm:text-sm px-3 py-2 rounded shadow">
                    Volver a ordenar
                </button>
            </div>

            <div v-if="props.orders.length">
                <div v-for="order in props.orders" :key="order.id_pedido"
                    class="mb-4 p-4 rounded shadow border border-[#d8c6ad] dark:border-[#6c4f3c] relative overflow-hidden"
                    :style="{ backgroundColor: order.estadopedido.color_codigo + '22' }">

                    <!-- Bandas laterales -->
                    <div class="absolute top-0 left-0 h-full w-2"
                        :style="{ backgroundColor: order.estadopedido?.color_codigo || '#ccc' }"></div>
                    <div class="absolute top-0 right-0 h-full w-2"
                        :style="{ backgroundColor: order.estadopedido?.color_codigo || '#ccc' }"></div>

                    <div class="flex justify-between items-start text-sm text-black dark:text-white">
                        <div>
                            <p class="font-semibold">Pedido #{{ order.id_pedido }}</p>
                            <p class="text-xs">{{ new Date(order.fecha_hora_registro).toLocaleString() }}</p>
                            <div class="flex items-center gap-2 mt-1">
                                <span class="w-3 h-3 rounded-full"
                                    :style="{ backgroundColor: order.estadopedido.color_codigo }"></span>
                                <span class="px-2 py-0.5 rounded text-xs font-semibold"
                                    :class="{
                                        'text-black': true,
                                        'dark:text-black': true
                                    }"
                                    :style="{ backgroundColor: order.estadopedido.color_codigo }">
                                    {{ order.estadopedido.nombre_estado }}
                                </span>
                            </div>
                        </div>

                        <div class="flex flex-col sm:flex-row gap-2 mt-2 sm:mt-0">
                            <button @click="abrirResumen(order.id_pedido)"
                                class="bg-blue-600 hover:bg-blue-700 text-white text-xs px-3 py-1 rounded shadow">
                                Ver resumen
                            </button>

                            <button @click="() => { }" class="text-white text-xs px-3 py-1 rounded shadow"
                                :class="(authUser?.id_rol === 1 || authUser?.id_rol === 2) && order.estadopedido.nombre_estado !== 'Pagado'
                                    ? 'bg-yellow-600 hover:bg-yellow-700'
                                    : 'bg-gray-400 cursor-not-allowed'"
                                :disabled="order.estadopedido.nombre_estado === 'Pagado' || !authUser || !(authUser.id_rol === 1 || authUser.id_rol === 2)">
                                Editar
                            </button>

                            <button v-if="order.estadopedido.nombre_estado !== 'Cancelado'" @click="() => { }"
                                class="text-white text-xs px-3 py-1 rounded shadow"
                                :class="puedeCancelar(order.fecha_hora_registro) && order.estadopedido.nombre_estado !== 'Pagado'
                                    ? 'bg-red-600 hover:bg-red-700'
                                    : 'bg-gray-400 cursor-not-allowed'"
                                :disabled="order.estadopedido.nombre_estado === 'Pagado' || !puedeCancelar(order.fecha_hora_registro)">
                                Cancelar
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <p v-else class="text-sm text-gray-600">No tienes pedidos registrados.</p>
        </div>

        <!-- Modal Resumen -->
        <div v-if="showResumen && pedidoSeleccionado"
            class="fixed inset-0 bg-black/40 z-50 flex items-center justify-center p-4">
            <div class="bg-white dark:bg-[#2c211b] rounded-lg p-6 shadow-xl w-full max-w-md">
                <h2 class="text-lg font-bold mb-4">Resumen del Pedido #{{ pedidoSeleccionado.id_pedido }}</h2>
                <ul class="divide-y divide-[#c5a880] dark:divide-[#8c5c3b] max-h-64 overflow-y-auto mb-4">
                    <li v-for="item in pedidoSeleccionado.detallepedidos" :key="item.id_producto" class="py-2">
                        <div class="flex justify-between">
                            <div>
                                <p class="font-medium">{{ item.producto.nombre }}</p>
                                <p class="text-xs text-gray-500">Cantidad: {{ item.cantidad }}</p>
                                <p v-if="item.comentario" class="text-xs italic mt-1 text-gray-700 dark:text-gray-300">"{{ item.comentario }}"</p>
                            </div>
                            <p class="font-semibold">{{ (item.producto.precio * item.cantidad).toFixed(2) }} Bs</p>
                        </div>
                    </li>
                </ul>
                <div class="flex justify-end">
                    <button @click="cerrarResumen"
                        class="px-4 py-2 rounded border hover:bg-neutral-100 dark:hover:bg-[#3a2e26]">
                        Cerrar
                    </button>
                </div>
            </div>
        </div>
    </AppLayout>
</template>