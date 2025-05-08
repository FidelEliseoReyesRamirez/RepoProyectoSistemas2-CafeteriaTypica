<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, router, usePage } from '@inertiajs/vue3';
import { ref, computed, onMounted, onUnmounted } from 'vue';
import type { PageProps } from '@/types';

const page = usePage<PageProps>();
const authUser = computed(() => page.props.auth?.user ?? null);

// Ahora como ref porque los vamos a actualizar con el fetch
const estadosCancelables = ref<string[]>(page.props.config?.estados_cancelables ?? []);
const estadosEditables = ref<string[]>(page.props.config?.estados_editables ?? []);
const tiemposPorEstado = ref<Record<string, { cancelar: number; editar: number }>>(page.props.config?.tiempos_por_estado ?? {});
const serverNow = ref(new Date(page.props.now ?? new Date().toISOString()).getTime());

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

const orders = ref(props.orders);
const previousStates = ref<Record<number, string>>({});
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
    return orders.value.find(o => o.id_pedido === selectedOrder.value);
});

const puedeCancelar = (orderDate: string, estado: string): boolean => {
    if (!authUser.value) return false;
    const rol = authUser.value.id_rol;
    if (!estadosCancelables.value.includes(estado)) return false;
    if (rol === 1) return true;

    const fechaPedido = new Date(orderDate).getTime();
    const limite = (tiemposPorEstado.value[estado]?.cancelar ?? 0) * 60 * 1000;

    return limite === 0 || (serverNow.value - fechaPedido) <= limite;
};

const puedeEditar = (orderDate: string, estado: string): boolean => {
    if (!authUser.value) return false;
    const rol = authUser.value.id_rol;
    if (!estadosEditables.value.includes(estado)) return false;
    if (rol === 1) return true;

    const fechaPedido = new Date(orderDate).getTime();
    const limite = (tiemposPorEstado.value[estado]?.editar ?? 0) * 60 * 1000;

    return limite === 0 || (serverNow.value - fechaPedido) <= limite;
};

const actualizarPedidos = async () => {
    try {
        const response = await fetch('/api/my-orders');
        if (!response.ok) throw new Error('No se pudieron obtener los pedidos');
        const data = await response.json();

        for (const pedido of data.orders) {
            previousStates.value[pedido.id_pedido] = pedido.estadopedido.nombre_estado;
        }

        orders.value = data.orders;
        serverNow.value = new Date(data.now).getTime();
        estadosCancelables.value = data.config.estados_cancelables;
        estadosEditables.value = data.config.estados_editables;
        tiemposPorEstado.value = data.config.tiempos_por_estado;
    } catch (error) {
        console.error('Error actualizando pedidos:', error);
    }
};

let intervaloActualizacion: number;

onMounted(() => {
    for (const pedido of orders.value) {
        previousStates.value[pedido.id_pedido] = pedido.estadopedido.nombre_estado;
    }
    intervaloActualizacion = setInterval(actualizarPedidos, 1000);
});

onUnmounted(() => {
    clearInterval(intervaloActualizacion);
});

const showCancelarModal = ref(false);
const showRehacerModal = ref(false);
const pedidoConfirmacionId = ref<number | null>(null);

const confirmarCancelar = (id: number) => {
    pedidoConfirmacionId.value = id;
    showCancelarModal.value = true;
};

const confirmarRehacer = (id: number) => {
    pedidoConfirmacionId.value = id;
    showRehacerModal.value = true;
};

const cancelarPedido = () => {
    if (!pedidoConfirmacionId.value) return;
    router.put(`/order/${pedidoConfirmacionId.value}/cancelar`);
    showCancelarModal.value = false;
    pedidoConfirmacionId.value = null;
};

const rehacerPedido = () => {
    if (!pedidoConfirmacionId.value) return;
    router.put(`/order/${pedidoConfirmacionId.value}/rehacer`);
    showRehacerModal.value = false;
    pedidoConfirmacionId.value = null;
};

const filtroNumero = ref('');
const filtroEstado = ref('');
const filtroTiempo = ref('');

const estadosDisponibles = computed(() => {
    return [...new Set(props.orders.map(order => order.estadopedido.nombre_estado))];
});

const filtrarPorTiempo = (fecha: string) => {
    const ahora = new Date();
    const fechaPedido = new Date(fecha);

    switch (filtroTiempo.value) {
        case 'ultima_hora':
            return ahora.getTime() - fechaPedido.getTime() <= 60 * 60 * 1000;
        case 'ultimas_2':
            return ahora.getTime() - fechaPedido.getTime() <= 2 * 60 * 60 * 1000;
        case 'hoy':
            return fechaPedido.toDateString() === ahora.toDateString();
        case 'ultimas_24':
            return ahora.getTime() - fechaPedido.getTime() <= 24 * 60 * 60 * 1000;
        case 'ultimos_2_dias':
            return ahora.getTime() - fechaPedido.getTime() <= 2 * 24 * 60 * 60 * 1000;
        case 'ultima_semana':
            return ahora.getTime() - fechaPedido.getTime() <= 7 * 24 * 60 * 60 * 1000;
        case 'este_mes':
            return (
                fechaPedido.getMonth() === ahora.getMonth() &&
                fechaPedido.getFullYear() === ahora.getFullYear()
            );
        default:
            return true;
    }
};

const pedidosFiltrados = computed(() => {
    return orders.value.filter(order => {
        const coincideNumero = !filtroNumero.value || order.id_pedido.toString().includes(filtroNumero.value);
        const coincideEstado = !filtroEstado.value || order.estadopedido.nombre_estado === filtroEstado.value;
        const coincideTiempo = filtrarPorTiempo(order.fecha_hora_registro);
        return coincideNumero && coincideEstado && coincideTiempo;
    });
});
console.log('Tiempos por estado:', tiemposPorEstado.value);


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
            <div class="mb-6 flex flex-col md:flex-row flex-wrap gap-4 items-start md:items-center">
                <!-- Búsqueda por número -->
                <input v-model="filtroNumero" type="text" placeholder="Buscar por # de pedido"
                    class="border text-black rounded px-3 py-2 text-sm w-full md:w-48" />

                <!-- Filtrar por estado -->
                <select v-model="filtroEstado" class="border text-black rounded px-3 py-2 text-sm w-full md:w-48">
                    <option value="">Todos los estados</option>
                    <option v-for="estado in estadosDisponibles" :key="estado" :value="estado">{{ estado }}</option>
                </select>

                <!-- Filtrar por tiempo -->
                <select v-model="filtroTiempo" class="border text-black rounded px-3 py-2 text-sm w-full md:w-48">
                    <option value="">Todo el tiempo</option>
                    <option value="ultima_hora">Última hora</option>
                    <option value="ultimas_2">Últimas 2 horas</option>
                    <option value="hoy">Hoy</option>
                    <option value="ultimas_24">Últimas 24 horas</option>
                    <option value="ultimos_2_dias">Últimos 2 días</option>
                    <option value="ultima_semana">Últimos 7 días</option>
                    <option value="este_mes">Este mes</option>
                </select>

                <!-- Botón para limpiar filtros -->
                <button @click="() => { filtroNumero = ''; filtroEstado = ''; filtroTiempo = '' }"
                    class="text-xs bg-red-500 hover:bg-red-600 text-white rounded px-3 py-2">
                    Limpiar filtros
                </button>
            </div>

            <div v-if="orders.length">
                <div v-for="order in pedidosFiltrados" :key="order.id_pedido"
                    class="mb-4 p-4 rounded shadow border border-[#d8c6ad] dark:border-[#6c4f3c] relative overflow-hidden"
                    :style="{ backgroundColor: order.estadopedido.color_codigo + '22' }">
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
                                <span class="px-2 py-0.5 rounded text-xs font-semibold" :class="['Cancelado', 'Entregado', 'Pagado'].includes(order.estadopedido.nombre_estado)
                                    ? 'text-white'
                                    : 'text-black dark:text-black'"
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

                            <button @click="router.visit(`/order/edit/${order.id_pedido}`, { preserveState: true })"
                                class="text-white text-xs px-3 py-1 rounded shadow" :class="puedeEditar(order.fecha_hora_registro, order.estadopedido.nombre_estado)
                                    ? 'bg-yellow-600 hover:bg-yellow-700'
                                    : 'bg-gray-400 cursor-not-allowed'"
                                :disabled="!puedeEditar(order.fecha_hora_registro, order.estadopedido.nombre_estado)">
                                Editar
                            </button>

                            <button v-if="order.estadopedido.nombre_estado !== 'Cancelado'"
                                @click="confirmarCancelar(order.id_pedido)" :class="[
                                    'text-white text-xs px-3 py-1 rounded shadow',
                                    puedeCancelar(order.fecha_hora_registro, order.estadopedido.nombre_estado)
                                        ? 'bg-red-600 hover:bg-red-700'
                                        : 'bg-gray-400 cursor-not-allowed'
                                ]" :disabled="!puedeCancelar(order.fecha_hora_registro, order.estadopedido.nombre_estado)">
                                Cancelar
                            </button>
                            <button v-if="order.estadopedido.nombre_estado === 'Cancelado'"
                                @click="confirmarRehacer(order.id_pedido)"
                                class="bg-indigo-600 hover:bg-indigo-700 text-white text-xs px-3 py-1 rounded shadow">
                                Restaurar
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <p v-else class="text-sm text-gray-600">No se encontraron pedidos con los filtros aplicados.</p>

        </div>

        <!-- Modal -->
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
                                    "{{ item.comentario }}"</p>
                            </div>
                            <p class="font-semibold">{{ (item.producto.precio * item.cantidad).toFixed(2) }} Bs</p>
                        </div>
                    </li>
                </ul>

                <!-- Total del pedido -->
                <div class="flex justify-between font-bold border-t pt-2">
                    <p>Total:</p>
                    <p>
                        {{
                            pedidoSeleccionado.detallepedidos
                                .reduce((sum, item) => sum + item.producto.precio * item.cantidad, 0)
                                .toFixed(2)
                        }} Bs
                    </p>
                </div>

                <div class="flex justify-end mt-4">
                    <button @click="cerrarResumen"
                        class="px-4 py-2 rounded border hover:bg-neutral-100 dark:hover:bg-[#3a2e26]">
                        Cerrar
                    </button>
                </div>
            </div>
        </div>

        <div v-if="showCancelarModal" class="fixed inset-0 bg-black/40 z-50 flex items-center justify-center p-4">
            <div class="bg-white dark:bg-[#2c211b] p-6 rounded-lg shadow-xl w-full max-w-md">
                <h2 class="text-lg font-bold mb-4">¿Cancelar pedido?</h2>
                <p class="text-sm mb-4">Una vez cancelado, este pedido ya no será procesado. ¿Está seguro?</p>
                <div class="flex justify-end gap-2">
                    <button @click="showCancelarModal = false"
                        class="px-4 py-2 border rounded hover:bg-neutral-100 dark:hover:bg-[#3a2e26]">
                        No
                    </button>
                    <button @click="cancelarPedido" class="bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded">
                        Sí, cancelar
                    </button>
                </div>
            </div>
        </div>


        <div v-if="showRehacerModal" class="fixed inset-0 bg-black/40 z-50 flex items-center justify-center p-4">
            <div class="bg-white dark:bg-[#2c211b] p-6 rounded-lg shadow-xl w-full max-w-md">
                <h2 class="text-lg font-bold mb-4">¿Rehacer pedido?</h2>
                <p class="text-sm mb-4">El pedido se reenviará a cocina y será marcado como pendiente. ¿Está seguro?</p>
                <div class="flex justify-end gap-2">
                    <button @click="showRehacerModal = false"
                        class="px-4 py-2 border rounded hover:bg-neutral-100 dark:hover:bg-[#3a2e26]">
                        No
                    </button>
                    <button @click="rehacerPedido"
                        class="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded">
                        Sí, rehacer
                    </button>
                </div>
            </div>
        </div>

    </AppLayout>
</template>
