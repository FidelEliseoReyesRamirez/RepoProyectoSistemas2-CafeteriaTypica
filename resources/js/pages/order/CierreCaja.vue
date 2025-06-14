<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import { ref, onMounted, computed, watch } from 'vue';
import axios from 'axios';
import Datepicker from 'vue3-datepicker';

const props = defineProps<{ fechas: string[] }>();

const fechasDisponibles = computed(() =>
    props.fechas.map(f => new Date(f + 'T00:00:00'))
);

const fechaInicio = ref<Date | undefined>();
const fechaFin = ref<Date | undefined>();

const resumen = ref<{ Efectivo: number, Tarjeta: number, QR: number, Total: number } | null>(null);
const metodoFiltro = ref<'Todos' | 'Efectivo' | 'Tarjeta' | 'QR'>('Todos');

watch([fechaInicio, fechaFin, metodoFiltro], () => {
    if (fechaInicio.value) {
        cargarPedidos(metodoFiltro.value);
    }
});

const pedidos = ref<any[]>([]);
const mostrarPedidos = ref(false);
const metodos = ['Todos', 'Efectivo', 'Tarjeta', 'QR'] as const;

const todasColumnas = [
    'ID', 'Fecha', 'Mesero', 'Estado', 'Producto', 'Cantidad', 'Comentario', 'Precio Unitario', 'Subtotal',
];
const columnasSeleccionadas = ref<string[]>([]);

const cargarColumnas = () => {
    const guardadas = localStorage.getItem('columnasSeleccionadas');
    columnasSeleccionadas.value = guardadas ? JSON.parse(guardadas) : [...todasColumnas];
};

const guardarColumnas = () => {
    localStorage.setItem('columnasSeleccionadas', JSON.stringify(columnasSeleccionadas.value));
};

const toggleSeleccionarTodo = () => {
    columnasSeleccionadas.value.length === todasColumnas.length
        ? columnasSeleccionadas.value = []
        : columnasSeleccionadas.value = [...todasColumnas];
};

const mostrarModal = ref(false);
const abrirModal = () => mostrarModal.value = true;
const cerrarModal = () => mostrarModal.value = false;

const exportarExcel = () => {
    guardarColumnas();
    const params = new URLSearchParams();
    columnasSeleccionadas.value.forEach(col => params.append('columnas[]', col));
    if (fechaInicio.value) params.set('fecha_inicio', formatDate(fechaInicio.value)!);
    if (fechaFin.value) params.set('fecha_fin', formatDate(fechaFin.value)!);
    window.location.href = `/exportar-pedidos?${params.toString()}`;
    cerrarModal();
};


const formatDate = (date?: Date): string | null =>
    date ? date.toISOString().split('T')[0] : null;

const cargarResumen = async () => {
    const inicio = formatDate(fechaInicio.value);
    const fin = formatDate(fechaFin.value);
    if (!inicio) return;
    try {
        const url = fin ? `/cierre-caja/resumen/${inicio}/${fin}` : `/cierre-caja/resumen/${inicio}`;
        const { data } = await axios.get(url);
        resumen.value = data;
    } catch (error) {
        console.error(error);
    }
};

const cargarPedidos = async (metodo: typeof metodos[number]) => {
    const inicio = formatDate(fechaInicio.value);
    const fin = formatDate(fechaFin.value);
    if (!inicio) return;
    try {
        const url = fin ? `/cierre-caja/pedidos/${inicio}/${fin}` : `/cierre-caja/pedidos/${inicio}`;
        const { data } = await axios.get(url, {
            params: metodo !== 'Todos' ? { metodo } : {},
        });
        pedidos.value = [...new Map(data.map((p: any) => [p.id_pedido, p])).values()];

        mostrarPedidos.value = true;
    } catch (error) {
        console.error(error);
    }
};

onMounted(() => {
    if (props.fechas.length) {
        fechaInicio.value = new Date(props.fechas[0] + 'T00:00:00');
        cargarResumen();
    }
    cargarColumnas();
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

const volver = () => router.visit('/cashier-orders');

const pedidoSeleccionado = ref<any | null>(null);
const showResumen = ref(false);

const abrirResumen = (pedido: any) => {
    pedidoSeleccionado.value = pedido;
    showResumen.value = true;
};

const cerrarResumen = () => {
    pedidoSeleccionado.value = null;
    showResumen.value = false;
};
</script>

<template>
    <AppLayout>

        <Head title="Cierre de Caja" />
        <div class="p-4 sm:p-6 text-[#4b3621] dark:text-white w-full max-w-screen-xl mx-auto">
            <div class="flex flex-col sm:flex-row sm:justify-between sm:items-center gap-6 mb-6">
                <h1 class="text-2xl sm:text-3xl font-bold">Cierre de Caja</h1>
                <div class="flex gap-2 sm:ml-auto">
                    <button @click="volver"
                        class="bg-gray-500 hover:bg-gray-600 text-white text-sm px-4 py-2 rounded shadow">
                        Volver
                    </button>
                    <button @click="abrirModal"
                        class="bg-indigo-600 hover:bg-indigo-700 text-white text-sm px-4 py-2 rounded shadow">
                        Exportar a Excel
                    </button>
                </div>
            </div>

            <div v-if="mostrarModal" class="fixed inset-0 bg-black/30 z-50 flex items-center justify-center">
                <div class="bg-white dark:bg-[#2c211b] p-6 rounded-lg shadow-xl max-w-md w-full">
                    <h2 class="text-lg font-semibold mb-4">Selecciona las columnas a exportar</h2>
                    <div class="grid grid-cols-2 gap-2 mb-4 text-sm">
                        <label v-for="col in todasColumnas" :key="col" class="flex items-center gap-1">
                            <input type="checkbox" :value="col" v-model="columnasSeleccionadas"
                                class="text-indigo-600" />
                            {{ col }}
                        </label>
                    </div>
                    <div class="flex justify-between items-center mb-4">
                        <button @click="toggleSeleccionarTodo" class="text-sm text-blue-600 hover:underline">
                            {{ columnasSeleccionadas.length === todasColumnas.length ? 'Deseleccionar todo' :
                                'Seleccionar todo' }}
                        </button>
                    </div>
                    <div class="flex justify-end gap-2">
                        <button @click="cerrarModal" class="px-4 py-2 text-sm rounded border">
                            Cancelar
                        </button>
                        <button @click="exportarExcel"
                            class="bg-green-600 hover:bg-green-700 text-white text-sm px-4 py-2 rounded shadow">
                            Exportar
                        </button>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 mb-6">
                <div>
                    <label class="block mb-1 text-sm font-semibold">Fecha de inicio:</label>
                    <Datepicker :model-value="fechaInicio ?? undefined"
                        @update:model-value="fechaInicio = $event ?? undefined" placeholder="Selecciona fecha de inicio"
:input-class="'w-full border px-3 py-2 rounded text-black placeholder:text-gray-500 bg-white dark:bg-[#1e1e1e] dark:text-white'"
                        :format="'yyyy-MM-dd'" :available-dates="{ dates: fechasDisponibles }" />

                </div>

                <div>
                    <label class="block mb-1 text-sm font-semibold">Fecha de fin (opcional):</label>
                    <Datepicker :model-value="fechaFin ?? undefined"
                        @update:model-value="fechaFin = $event ?? undefined" placeholder="Selecciona fecha de fin"
                        :input-class="'w-full border border-black px-3 py-2 rounded bg-white text-black placeholder:text-gray-500'"
                        :format="'yyyy-MM-dd'" :available-dates="{ dates: fechasDisponibles }"
                        :disabled-dates="{ predicate: (d: Date) => !!fechaInicio && d < fechaInicio }" />
                </div>

                <div>
                    <label class="block mb-1 text-sm font-semibold">Método de pago:</label>
                    <select v-model="metodoFiltro" class="w-full border px-3 py-2 rounded text-black">
                        <option v-for="metodo in metodos" :key="metodo" :value="metodo">
                            {{ metodo }} - Ver pedidos
                        </option>
                    </select>
                </div>
            </div>

            <div v-if="resumenFiltrado" class="w-full overflow-x-auto max-w-full block">
                <table class="table-auto w-full bg-white dark:bg-[#2c211b] rounded shadow text-xs sm:text-sm">
                    <thead>
                        <tr class="bg-[#f5ebe0] dark:bg-[#3b2e27] text-left">
                            <th class="px-4 py-2">Método de Pago</th>
                            <th class="px-4 py-2">Monto (Bs)</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="(monto, metodo) in resumenFiltrado" :key="metodo" class="border-t border-gray-200">
                            <td class="px-4 py-2 whitespace-normal">{{ metodo }}</td>
                            <td class="px-4 py-2 font-semibold whitespace-normal">{{ monto.toFixed(2) }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <p v-else class="text-sm text-gray-600 mt-6">Selecciona una fecha para ver el resumen.</p>

            <div v-if="mostrarPedidos" class="mt-8 w-full overflow-x-auto max-w-full block">
                <h2 class="text-lg sm:text-xl font-bold mb-4">Pedidos - {{ metodoFiltro }}</h2>
                <table class="table-auto w-full bg-white dark:bg-[#2c211b] rounded shadow text-xs sm:text-sm">
                    <thead>
                        <tr class="bg-[#e6d3ba] dark:bg-[#3b2e27] text-left">
                            <th class="px-4 py-2">ID</th>
                            <th class="px-4 py-2">Fecha</th>
                            <th class="px-4 py-2">Total</th>
                            <th class="px-4 py-2">Resumen</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="pedido in pedidos" :key="pedido.id_pedido" class="border-t border-gray-200">
                            <td class="px-4 py-2 whitespace-normal">#{{ pedido.id_pedido }}</td>
                            <td class="px-4 py-2 whitespace-normal">
                                {{ new Date(pedido.fecha).toLocaleString('es-BO') }}
                            </td>
                            <td class="px-4 py-2 whitespace-normal">
                                {{ pedido.monto.toFixed(2) }}
                            </td>
                            <td class="px-4 py-2">
                                <button @click="abrirResumen(pedido)"
                                    class="bg-blue-600 hover:bg-blue-700 text-white text-xs px-3 py-1 rounded shadow">
                                    Ver resumen
                                </button>
                            </td>
                        </tr>
                    </tbody>
                </table>
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
                                <p class="font-semibold whitespace-nowrap">
                                    {{ (item.precio * item.cantidad).toFixed(2) }} Bs
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
        </div>
    </AppLayout>
</template>
