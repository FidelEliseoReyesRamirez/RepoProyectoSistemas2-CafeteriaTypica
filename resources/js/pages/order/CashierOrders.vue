<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, router, usePage } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import type { PageProps } from '@/types';

const page = usePage<PageProps>();
const serverNow = ref(new Date(page.props.now ?? new Date().toISOString()).getTime());

const props = defineProps<{
    orders: {
        id_pedido: number;
        fecha_hora_registro: string;
        estadopedido: {
            nombre_estado: string;
            color_codigo: string;
        };
        usuario_mesero: {
            id_usuario: number;
            nombre: string;
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

const orders = ref(props.orders);
const selectedOrder = ref<number | null>(null);
const showResumen = ref(false);
const metodoPago = ref<'Efectivo' | 'Tarjeta' | 'QR' | ''>('');
const showPagoModal = ref(false);

const abrirResumen = (id: number) => {
    selectedOrder.value = id;
    showResumen.value = true;
};

const cerrarResumen = () => {
    selectedOrder.value = null;
    showResumen.value = false;
};

const pedidoSeleccionado = computed(() => {
    return orders.value.find(o => o.id_pedido === selectedOrder.value);
});
import axios from 'axios';
const marcarComoPagado = async () => {
  if (!selectedOrder.value || !metodoPago.value) return;
  try {
    const { data } = await axios.post(`/order/${selectedOrder.value}/pagar`, {
      metodo_pago: metodoPago.value
    });

    const pedido = orders.value.find(p => p.id_pedido === selectedOrder.value);
    if (pedido) {
      pedido.estadopedido = data.nuevo_estado;
    }

    showPagoModal.value = false;
    selectedOrder.value = null;
    metodoPago.value = '';
  } catch (error) {
    console.error(error);
  }
};

const rehacerPago = async (id: number) => {
  try {
    const { data } = await axios.put(`/order/${id}/no-pagado`);
    const pedido = orders.value.find(p => p.id_pedido === id);
    if (pedido) {
      pedido.estadopedido = data.nuevo_estado;
    }
  } catch (error) {
    console.error(error);
  }
};




const abrirPagoModal = (id: number) => {
    selectedOrder.value = id;
    showPagoModal.value = true;
};

const cerrarPagoModal = () => {
    selectedOrder.value = null;
    metodoPago.value = '';
    showPagoModal.value = false;
};

const totalPedido = (detalle: typeof props.orders[0]['detallepedidos']) => {
    return detalle.reduce((sum, item) => sum + item.producto.precio * item.cantidad, 0).toFixed(2);
};
</script>

<template>
    <AppLayout>

        <Head title="Caja - Pedidos" />
        <div class="p-4 sm:p-6 text-[#4b3621] dark:text-white">
            <h1 class="text-2xl font-bold mb-4">Pedidos en Caja</h1>

            <div v-if="orders.length">
                <div v-for="order in orders" :key="order.id_pedido"
                    class="mb-4 p-4 rounded shadow border border-[#d8c6ad] dark:border-[#6c4f3c] relative overflow-hidden"
                    :style="{ backgroundColor: order.estadopedido.color_codigo + '22' }">
                    <div class="absolute top-0 left-0 h-full w-2"
                        :style="{ backgroundColor: order.estadopedido?.color_codigo || '#ccc' }"></div>

                    <div class="flex justify-between items-start text-sm text-black dark:text-white">
                        <div>
                            <p class="font-semibold">Pedido #{{ order.id_pedido }}</p>
                            <p class="text-xs">{{ new Date(order.fecha_hora_registro).toLocaleString() }}</p>
                            <p class="text-xs font-medium">
                                Mesero: {{ order.usuario_mesero?.nombre ?? 'Sin asignar' }}
                            </p>
                            <p class="text-xs font-medium mt-1">Total: {{ totalPedido(order.detallepedidos) }} Bs</p>
                            <div class="flex items-center gap-2 mt-1">
                                <span class="w-3 h-3 rounded-full"
                                    :style="{ backgroundColor: order.estadopedido.color_codigo }"></span>
                                <span class="px-2 py-0.5 rounded text-xs font-semibold"
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
                            <button v-if="order.estadopedido.nombre_estado !== 'Pagado'"
                                @click="abrirPagoModal(order.id_pedido)"
                                class="bg-green-600 hover:bg-green-700 text-white text-xs px-3 py-1 rounded shadow">
                                Marcar como pagado
                            </button>
                            <button v-if="order.estadopedido.nombre_estado === 'Pagado'"
                                @click="rehacerPago(order.id_pedido)"
                                class="bg-yellow-500 hover:bg-yellow-600 text-white text-xs px-3 py-1 rounded shadow">
                                Marcar como no pagado
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <p v-else class="text-sm text-gray-600">No hay pedidos disponibles.</p>
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
                                <p v-if="item.comentario" class="text-xs italic mt-1 text-gray-700 dark:text-gray-300">
                                    "{{ item.comentario }}"
                                </p>
                            </div>
                            <p class="font-semibold">{{ (item.producto.precio * item.cantidad).toFixed(2) }} Bs</p>
                        </div>
                    </li>
                </ul>
                <div class="flex justify-between font-bold border-t pt-2">
                    <p>Total:</p>
                    <p>{{ totalPedido(pedidoSeleccionado.detallepedidos) }} Bs</p>
                </div>
                <div class="flex justify-end mt-4">
                    <button @click="cerrarResumen"
                        class="px-4 py-2 rounded border hover:bg-neutral-100 dark:hover:bg-[#3a2e26]">Cerrar</button>
                </div>
            </div>
        </div>

        <!-- Modal Pago -->
        <div v-if="showPagoModal" class="fixed inset-0 bg-black/40 z-50 flex items-center justify-center p-4">
            <div class="bg-white dark:bg-[#2c211b] rounded-lg p-6 shadow-xl w-full max-w-md">
                <h2 class="text-lg font-bold mb-4 ">Seleccionar método de pago</h2>
                <select v-model="metodoPago" class="w-full mb-4 border rounded px-3 py-2 text-black">
                    <option disabled value="">Seleccione una opción</option>
                    <option value="Efectivo">Efectivo</option>
                    <option value="Tarjeta">Tarjeta</option>
                    <option value="QR">QR</option>
                </select>
                <div class="flex justify-end gap-2">
                    <button @click="cerrarPagoModal" class="px-4 py-2 border rounded">Cancelar</button>
                    <button @click="marcarComoPagado"
                        class="bg-green-600 hover:bg-green-700  px-4 py-2 rounded">
                        Confirmar pago
                    </button>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
