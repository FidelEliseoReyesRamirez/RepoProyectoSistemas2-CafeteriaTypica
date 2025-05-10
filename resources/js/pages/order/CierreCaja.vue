<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import { ref, onMounted, computed, watch } from 'vue';
import axios from 'axios';

const props = defineProps<{
    fechas: string[];
}>();

const fechaInicio = ref('');
const fechaFin = ref('');
const resumen = ref<{ Efectivo: number, Tarjeta: number, QR: number, Total: number } | null>(null);
const metodoFiltro = ref<'Todos' | 'Efectivo' | 'Tarjeta' | 'QR'>('Todos');
const pedidos = ref<{
    id_pedido: number,
    monto: number,
    fecha: string,
    mesero: string,
    detalles: { producto: string, cantidad: number, comentario?: string, precio: number }[]
}[]>([]);
const mostrarPedidos = ref(false);
const metodos = ['Todos', 'Efectivo', 'Tarjeta', 'QR'] as const;

const cargarResumen = async () => {
    if (!fechaInicio.value) return;
    try {
        const url = fechaFin.value
            ? `/cierre-caja/resumen/${fechaInicio.value}/${fechaFin.value}`
            : `/cierre-caja/resumen/${fechaInicio.value}`;
        const { data } = await axios.get(url);
        resumen.value = data;
    } catch (error) {
        console.error(error);
    }
};

const cargarPedidos = async (metodo: 'Todos' | 'Efectivo' | 'Tarjeta' | 'QR') => {
    if (!fechaInicio.value) return;
    try {
        const url = fechaFin.value
            ? `/cierre-caja/pedidos/${fechaInicio.value}/${fechaFin.value}`
            : `/cierre-caja/pedidos/${fechaInicio.value}`;
        const { data } = await axios.get(url, {
            params: metodo !== 'Todos' ? { metodo } : {},
        });
        pedidos.value = data;
        mostrarPedidos.value = true;
    } catch (error) {
        console.error(error);
    }
};

onMounted(() => {
    if (props.fechas.length) {
        fechaInicio.value = props.fechas[0];
        cargarResumen();
    }
});

watch([fechaInicio, fechaFin], cargarResumen);

const resumenFiltrado = computed(() => {
    if (!resumen.value) return null;
    if (metodoFiltro.value === 'Todos') return resumen.value;
    return {
        [metodoFiltro.value]: resumen.value[metodoFiltro.value],
        Total: resumen.value[metodoFiltro.value],
    };
});

const volver = () => {
    router.visit('/cashier-orders');
};

const pedidoSeleccionado = ref<typeof pedidos.value[0] | null>(null);
const showResumen = ref(false);

const abrirResumen = (pedido: typeof pedidos.value[0]) => {
    pedidoSeleccionado.value = pedido;
    showResumen.value = true;
};

const cerrarResumen = () => {
    pedidoSeleccionado.value = null;
    showResumen.value = false;
};

const fechaFinDeshabilitada = computed(() => {
    return fechaInicio.value && fechaFin.value && new Date(fechaFin.value) < new Date(fechaInicio.value);
});


const fechasDisponibles = computed(() => {
    return props.fechas.filter(fecha => new Date(fecha) >= new Date(fechaInicio.value));
});
</script>
<template>
    <AppLayout>
        <Head title="Cierre de Caja" />
        <div class="p-4 sm:p-6 text-[#4b3621] dark:text-white w-full max-w-7xl mx-auto overflow-x-hidden">
            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-6">
                <h1 class="text-2xl sm:text-3xl font-bold">Cierre de Caja</h1>
                <button @click="volver"
                    class="bg-gray-500 hover:bg-gray-600 text-white text-sm px-4 py-2 rounded shadow">
                    Volver
                </button>
                <a href="/exportar-pedidos"
                    class="bg-indigo-600 hover:bg-indigo-700 text-white text-sm px-4 py-2 rounded shadow">
                    Exportar a Excel
                </a>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 mb-6">
                <div>
                    <label class="block mb-1 text-sm font-semibold">Fecha de inicio:</label>
                    <select v-model="fechaInicio" class="w-full border px-3 py-2 rounded text-black">
                        <option disabled value="">-- Selecciona fecha --</option>
                        <option v-for="fecha in props.fechas" :key="fecha" :value="fecha">{{ fecha }}</option>
                    </select>
                </div>

                <div>
                    <label class="block mb-1 text-sm font-semibold">Fecha de fin (opcional):</label>
                    <select v-model="fechaFin" :disabled="!!fechaFinDeshabilitada"
                        class="w-full border px-3 py-2 rounded text-black">
                        <option value="">-- Sin fecha fin --</option>
                        <!-- Filtramos las fechas disponibles para fechaFin -->
                        <option v-for="fecha in fechasDisponibles" :key="fecha" :value="fecha">{{ fecha }}</option>
                    </select>
                </div>

                <div>
                    <label class="block mb-1 text-sm font-semibold">Método de pago:</label>
                    <select v-model="metodoFiltro" @change="cargarPedidos(metodoFiltro)"
                        class="w-full border px-3 py-2 rounded text-black">
                        <option v-for="metodo in metodos" :key="metodo" :value="metodo">
                            {{ metodo }} - Ver pedidos
                        </option>
                    </select>
                </div>
            </div>

            <div v-if="resumenFiltrado" class="w-full overflow-x-auto max-w-full block">
                <table class="table-fixed w-full bg-white dark:bg-[#2c211b] rounded shadow text-xs sm:text-sm">
                    <thead>
                        <tr class="bg-[#f5ebe0] dark:bg-[#3b2e27] text-left">
                            <th class="px-2 sm:px-4 py-2">Método de Pago</th>
                            <th class="px-2 sm:px-4 py-2">Monto (Bs)</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="(monto, metodo) in resumenFiltrado" :key="metodo" class="border-t border-gray-200">
                            <td class="px-2 sm:px-4 py-2 break-words whitespace-normal">{{ metodo }}</td>
                            <td class="px-2 sm:px-4 py-2 break-words whitespace-normal">{{ monto.toFixed(2) }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <p v-else class="text-sm text-gray-600 mt-6">Selecciona una fecha para ver el resumen.</p>

            <div v-if="mostrarPedidos" class="mt-8 w-full overflow-x-auto max-w-full block">
                <h2 class="text-lg sm:text-xl font-bold mb-4">Pedidos - {{ metodoFiltro }}</h2>
                <table class="table-fixed w-full bg-white dark:bg-[#2c211b] rounded shadow text-xs sm:text-sm">
                    <thead>
                        <tr class="bg-[#e6d3ba] dark:bg-[#3b2e27] text-left">
                            <th class="px-2 sm:px-4 py-2">ID</th>
                            <th class="px-2 sm:px-4 py-2">Fecha</th>
                            <th class="px-2 sm:px-4 py-2">Total</th>
                            <th class="px-2 sm:px-4 py-2">Resumen</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="pedido in pedidos" :key="pedido.id_pedido" class="border-t border-gray-200">
                            <td class="px-2 sm:px-4 py-2 break-words whitespace-normal">#{{ pedido.id_pedido }}</td>
                            <td class="px-2 sm:px-4 py-2 break-words whitespace-normal">{{ new
                                Date(pedido.fecha).toLocaleString('es-BO') }}</td>
                            <td class="px-2 sm:px-4 py-2 break-words whitespace-normal">{{ pedido.monto.toFixed(2) }}
                            </td>
                            <td class="px-2 sm:px-4 py-2">
                                <button @click="abrirResumen(pedido)"
                                    class="bg-blue-600 hover:bg-blue-700 text-white text-xs px-3 py-1 rounded shadow">
                                    Ver resumen
                                </button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <div v-if="showResumen && pedidoSeleccionado"
            class="fixed inset-0 bg-black/40 z-50 flex items-center justify-center p-4">
            <div class="bg-white dark:bg-[#2c211b] rounded-lg p-6 shadow-xl w-full max-w-md">
                <h2 class="text-lg font-bold mb-4">Resumen del Pedido #{{ pedidoSeleccionado.id_pedido }}</h2>
                <ul class="divide-y divide-[#c5a880] dark:divide-[#8c5c3b] max-h-64 overflow-y-auto mb-4 text-sm">
                    <li v-for="(item, index) in pedidoSeleccionado.detalles" :key="index" class="py-2">
                        <div class="flex justify-between gap-4">
                            <div class="min-w-0">
                                <p class="font-medium break-words">{{ item.producto }}</p>
                                <p class="text-xs text-gray-500">Cantidad: {{ item.cantidad }}</p>
                                <p v-if="item.comentario"
                                    class="text-xs italic mt-1 text-gray-700 dark:text-gray-300 break-words">
                                    "{{ item.comentario }}"
                                </p>
                            </div>
                            <p class="font-semibold whitespace-nowrap">{{ (item.precio * item.cantidad).toFixed(2) }} Bs
                            </p>
                        </div>
                    </li>
                </ul>
                <div class="flex justify-between font-bold border-t pt-2">
                    <p>Total:</p>
                    <p>{{ pedidoSeleccionado.monto.toFixed(2) }} Bs</p>
                </div>
                <div class="flex justify-end mt-4">
                    <button @click="cerrarResumen"
                        class="px-4 py-2 text-sm rounded border hover:bg-neutral-100 dark:hover:bg-[#3a2e26]">
                        Cerrar
                    </button>
                </div>
            </div>
        </div>
    </AppLayout>
</template>