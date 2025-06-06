<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, router, usePage } from '@inertiajs/vue3';
import { ref, computed, onMounted, onUnmounted } from 'vue';
import type { PageProps } from '@/types';
import axios from 'axios';

const page = usePage<PageProps>();
const authUser = computed(() => page.props.auth?.user ?? null);

const estadosCancelables = ref<string[]>(page.props.config?.estados_cancelables ?? []);
const estadosEditables = ref<string[]>(page.props.config?.estados_editables ?? []);
const tiemposPorEstado = ref<Record<string, { cancelar: number; editar: number }>>(page.props.config?.tiempos_por_estado ?? {});
const serverNow = ref(new Date(page.props.now ?? new Date().toISOString()).getTime());
const horarioAtencion = ref<Record<string, { hora_inicio: string; hora_fin: string }>>(page.props.config?.horario_atencion ?? {});

const showFueraHorarioModal = ref(false);

const cerrarFueraHorarioModal = () => {
    showFueraHorarioModal.value = false;
    pedidoConfirmacionId.value = null;
};

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

const estaEnHorario = (): boolean => {
    const now = new Date();
    const dia = now.toLocaleDateString('es-ES', { weekday: 'long' }).toLowerCase();
    const h = horarioAtencion.value?.[dia];
    if (!h) return false;
    const horaActual = now.toTimeString().slice(0, 5);
    return horaActual >= h.hora_inicio.slice(0, 5) && horaActual <= h.hora_fin.slice(0, 5);
};

const puedeCancelar = (orderDate: string, estado: string): boolean => {
    if (!authUser.value || !estaEnHorario()) return false;
    const rol = authUser.value.id_rol;
    if (!estadosCancelables.value.includes(estado)) return false;
    if (rol === 1) return true;
    const fechaPedido = new Date(orderDate).getTime();
    const limite = (tiemposPorEstado.value[estado]?.cancelar ?? 0) * 60 * 1000;
    const diferencia = serverNow.value - fechaPedido;
    return limite === 0 || diferencia <= limite;
};

const puedeEditar = (orderDate: string, estado: string): boolean => {
    if (!authUser.value || !estaEnHorario()) return false;
    const rol = authUser.value.id_rol;
    if (!estadosEditables.value.includes(estado)) return false;
    if (rol === 1) return true;
    const fechaPedido = new Date(orderDate).getTime();
    const limite = (tiemposPorEstado.value[estado]?.editar ?? 0) * 60 * 1000;
    const diferencia = serverNow.value - fechaPedido;
    return limite === 0 || diferencia <= limite;
};

const actualizarPedidos = async () => {
    try {
        const response = await fetch('/api/all-orders'); // Usa esta URL
        if (!response.ok) throw new Error('No se pudieron obtener los pedidos');
        const data = await response.json();

        orders.value = data.orders;
        serverNow.value = new Date(data.now).getTime();
        estadosCancelables.value = data.config.estados_cancelables;
        estadosEditables.value = data.config.estados_editables;
        tiemposPorEstado.value = data.config.tiempos_por_estado;
        horarioAtencion.value = data.config.horario_atencion ?? {};
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
    pedidoConfirmacionId.value = id; // Asegúrate de que el id se esté guardando correctamente
    if (!estaEnHorario()) {
        showFueraHorarioModal.value = true;
        return;
    }
    showCancelarModal.value = true; // Esto debería mostrar el modal de confirmación
};

const cancelarPedido = async () => {
    if (!pedidoConfirmacionId.value) return; // Asegúrate de que el ID no esté vacío
    try {
        await router.put(`/order/${pedidoConfirmacionId.value}/cancelar`); // Usamos await para esperar la acción
        showCancelarModal.value = false; // Cerrar el modal
        pedidoConfirmacionId.value = null; // Limpiar el ID de confirmación
    } catch (error) {
        console.error('Error al cancelar el pedido:', error); // Manejar errores si los hay
    }
};



const confirmarRehacer = (id: number) => {
    pedidoConfirmacionId.value = id;
    if (!estaEnHorario()) {
        showFueraHorarioModal.value = true;
        return;
    }
    showRehacerModal.value = true;
};

const confirmarEditar = (id: number) => {
    pedidoConfirmacionId.value = id;
    if (!estaEnHorario()) {
        showFueraHorarioModal.value = true;
        return;
    }
    router.visit(`/order/edit/${id}`, { preserveState: true });
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

    if (filtroTiempo.value === 'rango_fechas' && fechaInicio.value && fechaFin.value && !isFechaFinalInvalid.value) {
        const fechaIni = new Date(fechaInicio.value).getTime();
        const fechaFinVal = new Date(fechaFin.value).getTime();
        const fechaPedidoVal = fechaPedido.getTime();
        return fechaPedidoVal >= fechaIni && fechaPedidoVal <= fechaFinVal;
    }

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
            return fechaPedido.getMonth() === ahora.getMonth() && fechaPedido.getFullYear() === ahora.getFullYear();
        default:
            return true;
    }
};

const filtroMesero = ref('');

const meserosDisponibles = computed(() => {
    const nombres = props.orders.map(order => order.usuario_mesero.nombre);
    return [...new Set(nombres)];
});

const pedidosFiltrados = computed(() => {
    return orders.value.filter(order => {
        const coincideNumero = !filtroNumero.value || order.id_pedido.toString().includes(filtroNumero.value);
        const coincideEstado = !filtroEstado.value || order.estadopedido.nombre_estado === filtroEstado.value;
        const coincideTiempo = filtrarPorTiempo(order.fecha_hora_registro);
        const coincideMesero = !filtroMesero.value || order.usuario_mesero.nombre === filtroMesero.value;
        return coincideNumero && coincideEstado && coincideTiempo && coincideMesero;
    });
});

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


const columnasSeleccionadas = ref<string[]>([]);

// Definir la función formatDate para formatear la fecha a 'yyyy-MM-dd'
const formatDate = (date: Date): string => {
    return date.toISOString().split('T')[0];
};


const exportarExcel = () => {
    // Abre el modal cuando se hace clic en el botón de exportar
    showExportModal.value = true;
};

const showExportModal = ref(false);

const cerrarExportModal = () => {
    showExportModal.value = false;
};

const exportarSeleccionExcel = () => {
    const params = new URLSearchParams();

    // Añadir las columnas seleccionadas
    columnasSeleccionadas.value.forEach(col => params.append('columnas[]', col));

    // Añadir los filtros activos
    if (filtroNumero.value) params.set('numero', filtroNumero.value);
    if (filtroEstado.value) params.set('estado', filtroEstado.value);
    if (filtroTiempo.value) params.set('tiempo', filtroTiempo.value); // Asegúrate de enviar el filtro de tiempo
    if (filtroMesero.value) params.set('mesero', filtroMesero.value); // Asegúrate de enviar el filtro de mesero

    // Si se ha seleccionado un rango de fechas, añadir las fechas de inicio y fin
    if (fechaInicio.value) params.set('fecha_inicio', formatDate(new Date(fechaInicio.value))!);
    if (fechaFin.value) params.set('fecha_fin', formatDate(new Date(fechaFin.value))!);

    // Realizar la exportación redirigiendo a la URL con los parámetros filtrados
    window.location.href = `/exportar-pedidos?${params.toString()}`;
    cerrarExportModal();
};


const generarPDFAdmin = async (pedidoId: number) => {
    if (!pedidoId) {
        console.error('El ID del pedido no está definido');
        return;
    }

    try {
        const response = await axios.get(`/pedido/${pedidoId}/admin-pdf`, { responseType: 'blob' });

        // Crear un objeto URL a partir del blob recibido
        const url = window.URL.createObjectURL(new Blob([response.data], { type: 'application/pdf' }));

        // Abrir el PDF en una nueva pestaña
        const pdfWindow = window.open(url, '_blank');

        // Esperar a que el PDF se cargue y luego activar la impresión automática
        pdfWindow?.addEventListener('load', () => {
            pdfWindow?.print();
        });

    } catch (error) {
        console.error('Error generando PDF del Admin:', error);
    }
};
const toggleSeleccionarTodo = () => {
    if (columnasSeleccionadas.value.length === 9) {
        columnasSeleccionadas.value = [];
    } else {
        columnasSeleccionadas.value = ['ID', 'Fecha', 'Mesero', 'Estado', 'Producto', 'Cantidad', 'Comentario', 'Precio Unitario', 'Subtotal'];
    }
};

// --- refs para el modal y datos de estados ---
const showCambiarEstadoModal = ref(false);
const estadosPedido = ref<{ id_estado: number; nombre_estado: string; color_codigo: string }[]>([]);
const estadoSeleccionado = ref<number | null>(null);
const pedidoCambioId = ref<number | null>(null);

// --- cargar todos los estados al montar ---
onMounted(async () => {
    try {
        const res = await axios.get('/api/estados-pedido');
        estadosPedido.value = res.data;
    } catch (err) {
        console.error('>> Error fetching /api/estados-pedido:', err);
    }
});

// --- abrir modal: inicializa seleccionando por nombre_estado ---
const abrirCambiarEstado = (id: number) => {
    pedidoCambioId.value = id;
    const pedido = orders.value.find(o => o.id_pedido === id);
    // busco el id_estado que coincide con el nombre actual
    const match = estadosPedido.value.find(e => e.nombre_estado === pedido?.estadopedido.nombre_estado);
    estadoSeleccionado.value = match?.id_estado ?? null;
    showCambiarEstadoModal.value = true;
};

// --- cerrar modal ---
const cerrarCambiarEstado = () => {
    showCambiarEstadoModal.value = false;
    pedidoCambioId.value = null;
};

// --- enviar cambio al servidor y refrescar lista ---
const confirmarCambioEstado = async () => {
    if (!pedidoCambioId.value || !estadoSeleccionado.value) return;
    try {
        const res = await axios.put(`/order/${pedidoCambioId.value}/cambiar-estado`, {
            estado_id: estadoSeleccionado.value,
        });

        await actualizarPedidos();
        cerrarCambiarEstado();
    } catch (err: any) {              // ← y aquí
        console.error('>> Error PUT /order/.../cambiar-estado:', err.response ?? err);
    }
};


</script>

<template>
    <AppLayout>

        <Head title="Todos los Pedidos" />
        <div class="p-4 sm:p-6 text-[#4b3621] dark:text-white">
            <div class="flex justify-between items-center mb-4">
                <h1 class="text-2xl font-bold text-[#593E25] dark:text-[#d9a679]">Todos los Pedidos</h1>
            </div>

            <div class="mb-6 flex flex-col md:flex-row flex-wrap gap-4 items-start md:items-center">
                <input v-model="filtroNumero" type="text" placeholder="Buscar por # de pedido"
                    class="border text-black rounded px-3 py-2 text-sm w-full md:w-48" />

                <select v-model="filtroEstado" class="border text-black rounded px-3 py-2 text-sm w-full md:w-48">
                    <option value="">Todos los estados</option>
                    <option v-for="estado in estadosDisponibles" :key="estado" :value="estado">{{ estado }}</option>
                </select>

                <select v-model="filtroTiempo" class="border text-black rounded px-3 py-2 text-sm w-full md:w-48">
                    <option value="">Todo el tiempo</option>
                    <option value="ultima_hora">Última hora</option>
                    <option value="ultimas_2">Últimas 2 horas</option>
                    <option value="hoy">Hoy</option>
                    <option value="ultimas_24">Últimas 24 horas</option>
                    <option value="ultimos_2_dias">Últimos 2 días</option>
                    <option value="ultima_semana">Últimos 7 días</option>
                    <option value="este_mes">Este mes</option>
                    <option value="rango_fechas">Rango de fechas</option>
                </select>

                <div v-if="filtroTiempo === 'rango_fechas'" class="flex gap-4 items-center">

                    <input type="date" v-model="fechaInicio"
                        class="border text-black rounded px-3 py-2 text-sm w-full md:w-48" />

                    <input type="date" v-model="fechaFin" :min="fechaInicio || undefined"
                        class="border text-black rounded px-3 py-2 text-sm w-full md:w-48" />


                    <div v-if="isFechaFinalInvalid" class="text-red-500 text-sm mt-2">
                        La fecha final no puede ser anterior a la fecha de inicio.
                    </div>
                </div>

                <select v-model="filtroMesero" class="border text-black rounded px-3 py-2 text-sm w-full md:w-48">
                    <option value="">Todos los meseros</option>
                    <option v-for="mesero in meserosDisponibles" :key="mesero" :value="mesero">{{ mesero }}</option>
                </select>

                <button @click="exportarExcel" :disabled="isFechaFinalInvalid"
                    class="bg-green-600 hover:bg-green-700 text-white text-sm px-4 py-2 rounded shadow disabled:opacity-50 disabled:cursor-not-allowed">
                    Exportar a Excel
                </button>

                <button @click="() => { filtroNumero = ''; filtroEstado = ''; filtroTiempo = ''; filtroMesero = '' }"
                    class="text-sm bg-red-500 hover:bg-red-600 text-white rounded px-3 py-2">
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
                            <p class="text-xs font-medium">
                                Mesero: {{ order.usuario_mesero && order.usuario_mesero.nombre ?
                                    order.usuario_mesero.nombre : 'Sin asignar' }}
                            </p>

                            <div class="flex items-center gap-2 mt-1">
                                <span class="w-3 h-3 rounded-full"
                                    :style="{ backgroundColor: order.estadopedido.color_codigo }"></span>
                                <span class="px-2 py-0.5 rounded text-xs font-semibold" :class="['Cancelado', 'Entregado', 'Pagado'].includes(order.estadopedido.nombre_estado)
                                    ? 'text-white' : 'text-black dark:text-black'"
                                    :style="{ backgroundColor: order.estadopedido.color_codigo }">
                                    {{ order.estadopedido.nombre_estado }}
                                </span>
                            </div>
                        </div>

                        <div class="flex flex-col sm:flex-row gap-2 mt-2 sm:mt-0">
                            <button @click="generarPDFAdmin(order.id_pedido)"
                                class="text-xs bg-[#800080] hover:bg-[#9b4dff] text-white rounded px-2 py-1">
                                Exportar PDF
                            </button>

                            <button @click="abrirResumen(order.id_pedido)"
                                class="bg-blue-600 hover:bg-blue-700 text-white text-xs px-3 py-1 rounded shadow">
                                Ver resumen
                            </button>

                            <button @click="confirmarEditar(order.id_pedido)"
                                class="bg-yellow-600 hover:bg-yellow-700 text-white text-xs px-3 py-1 rounded shadow">
                                Editar
                            </button>

                            <button v-if="order.estadopedido.nombre_estado !== 'Cancelado'"
                                @click="confirmarCancelar(order.id_pedido)"
                                class="bg-red-600 hover:bg-red-700 text-white text-xs px-3 py-1 rounded shadow">
                                Cancelar
                            </button>

                            <button v-if="order.estadopedido.nombre_estado === 'Cancelado'"
                                @click="confirmarRehacer(order.id_pedido)"
                                class="bg-indigo-600 hover:bg-indigo-700 text-white text-xs px-3 py-1 rounded shadow">
                                Restaurar
                            </button>
                            <button @click="abrirCambiarEstado(order.id_pedido)"
                                class="bg-purple-600 hover:bg-purple-700 text-white text-xs px-3 py-1 rounded shadow">
                                Cambiar estado
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <p v-else class="text-sm text-gray-600">No se encontraron pedidos con los filtros aplicados.</p>
        </div>
        <!-- Modal de Cambio de Estado -->
        <div v-if="showCambiarEstadoModal" class="fixed inset-0 bg-black/30 z-50 flex items-center justify-center p-4">
            <div class="bg-white dark:bg-[#2c211b] p-6 rounded-lg shadow-xl max-w-sm w-full">
                <h2 class="text-lg font-semibold mb-4 text-[#593E25] dark:text-[#d9a679]">
                    Cambiar estado del Pedido #{{ pedidoCambioId }}
                </h2>
                <select v-model="estadoSeleccionado" class="border text-black rounded px-3 py-2 w-full mb-4">
                    <option v-for="e in estadosPedido" :key="e.id_estado" :value="e.id_estado">
                        {{ e.nombre_estado }}
                    </option>
                </select>
                <div class="flex justify-end gap-2">
                    <button @click="cerrarCambiarEstado"
                        class="px-4 py-2 rounded border hover:bg-neutral-100 dark:hover:bg-[#3a2e26]">
                        Cancelar
                    </button>
                    <button @click="confirmarCambioEstado"
                        class="bg-purple-600 hover:bg-purple-700 text-white px-4 py-2 rounded shadow">
                        Guardar
                    </button>
                </div>
            </div>
        </div>

        <!-- Modal Selección de Columnas -->
        <div v-if="showExportModal" class="fixed inset-0 bg-black/30 z-50 flex items-center justify-center p-4">
            <div class="bg-white dark:bg-[#2c211b] p-6 rounded-lg shadow-xl max-w-md w-full">
                <h2 class="text-lg font-semibold mb-4 text-[#593E25] dark:text-[#d9a679]">Selecciona las columnas a
                    exportar</h2>
                <div class="grid grid-cols-2 gap-2 mb-4 text-sm">
                    <label
                        v-for="col in ['ID', 'Fecha', 'Mesero', 'Estado', 'Producto', 'Cantidad', 'Comentario', 'Precio Unitario', 'Subtotal']"
                        :key="col" class="flex items-center gap-1">
                        <input type="checkbox" :value="col" v-model="columnasSeleccionadas"
                            class="text-indigo-600 dark:text-indigo-400" />
                        {{ col }}
                    </label>
                </div>
                <div class="flex justify-between items-center mb-4">
                    <button @click="toggleSeleccionarTodo"
                        class="text-sm text-blue-600 dark:text-blue-400 hover:underline">
                        {{ columnasSeleccionadas.length === 9 ? 'Deseleccionar todo' : 'Seleccionar todo' }}
                    </button>
                </div>
                <div class="flex justify-end gap-2">
                    <button @click="cerrarExportModal"
                        class="px-4 py-2 text-sm rounded border hover:bg-neutral-100 dark:hover:bg-[#3a2e26]">
                        Cancelar
                    </button>
                    <button @click="exportarSeleccionExcel"
                        class="bg-green-600 hover:bg-green-700 text-white text-sm px-4 py-2 rounded shadow">
                        Exportar
                    </button>
                </div>
            </div>
        </div>
        <!-- Modal de Confirmación para Restaurar Pedido -->
        <div v-if="showRehacerModal" class="fixed inset-0 bg-black/30 z-50 flex items-center justify-center p-4">
            <div class="bg-white dark:bg-[#2c211b] p-6 rounded-lg shadow-xl max-w-md w-full">
                <h2 class="text-lg font-semibold mb-4 text-[#593E25] dark:text-[#d9a679]">¿Estás seguro de que deseas
                    restaurar este pedido?</h2>
                <div class="flex justify-end gap-2">
                    <button @click="showRehacerModal = false"
                        class="px-4 py-2 text-sm rounded border hover:bg-neutral-100 dark:hover:bg-[#3a2e26]">
                        Cancelar
                    </button>
                    <button @click="rehacerPedido"
                        class="bg-indigo-600 hover:bg-indigo-700 text-white text-sm px-4 py-2 rounded shadow">
                        Confirmar Restauración
                    </button>
                </div>
            </div>
        </div>
        <!-- Modal de Resumen del Pedido -->
        <div v-if="showResumen && pedidoSeleccionado"
            class="fixed inset-0 bg-black/40 z-50 flex items-center justify-center p-4">
            <div class="bg-white dark:bg-[#2c211b] rounded-lg p-6 shadow-xl w-full max-w-md">
                <h2 class="text-lg font-bold mb-4">Resumen del Pedido #{{ pedidoSeleccionado.id_pedido }}</h2>

                <!-- Detalles de los productos -->
                <ul class="divide-y divide-[#c5a880] dark:divide-[#8c5c3b] max-h-64 overflow-y-auto mb-4">
                    <li v-for="item in pedidoSeleccionado.detallepedidos" :key="item.id_producto" class="py-2">
                        <div class="flex justify-between">
                            <div>
                                <p class="font-medium">{{ item.producto.nombre }}</p>
                                <p class="text-xs text-gray-500">Cantidad: {{ item.cantidad }}</p>
                                <p v-if="item.comentario" class="text-xs italic mt-1"
                                    :class="item.comentario.includes('Motivo rechazo:') ? 'text-red-600 font-semibold' : 'text-gray-700 dark:text-gray-300'">
                                    "{{ item.comentario }}"
                                </p>

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
                                // @ts-ignore:
                                .reduce((sum, item) => sum + item.producto.precio * item.cantidad, 0)
                                .toFixed(2)
                        }} Bs
                    </p>
                </div>

                <!-- Botón para cerrar el modal -->
                <div class="flex justify-end mt-4">
                    <button @click="cerrarResumen"
                        class="px-4 py-2 rounded border hover:bg-neutral-100 dark:hover:bg-[#3a2e26]">
                        Cerrar
                    </button>
                </div>
            </div>
        </div>

        <!-- Modal de Confirmación para Cancelar Pedido -->
        <div v-if="showCancelarModal" class="fixed inset-0 bg-black/30 z-50 flex items-center justify-center p-4">
            <div class="bg-white dark:bg-[#2c211b] p-6 rounded-lg shadow-xl max-w-md w-full">
                <h2 class="text-lg font-semibold mb-4 text-[#593E25] dark:text-[#d9a679]">¿Estás seguro de que deseas
                    cancelar este pedido?</h2>
                <div class="flex justify-end gap-2">
                    <button @click="showCancelarModal = false"
                        class="px-4 py-2 text-sm rounded border hover:bg-neutral-100 dark:hover:bg-[#3a2e26]">
                        Cancelar
                    </button>
                    <button @click="cancelarPedido"
                        class="bg-red-600 hover:bg-red-700 text-white text-sm px-4 py-2 rounded shadow">
                        Confirmar Cancelación
                    </button>
                </div>
            </div>
        </div>

    </AppLayout>
</template>
