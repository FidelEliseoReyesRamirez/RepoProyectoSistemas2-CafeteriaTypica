<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, router, usePage } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import type { PageProps } from '@/types';
import axios from 'axios';

// Props e Inertia
const page = usePage<PageProps>();
const serverNow = ref(new Date(page.props.now ?? new Date().toISOString()).getTime());

// Filtros
const filtroNumero = ref('');
const filtroTiempo = ref('');
const filtroEstado = ref('');            // ← Filtro de estado dinámico
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
    case 'ultima_hora':    return ahora.getTime() - fechaPedido.getTime() <=  3600000;
    case 'ultimas_2':      return ahora.getTime() - fechaPedido.getTime() <=  7200000;
    case 'hoy':            return fechaPedido.toDateString() === ahora.toDateString();
    case 'ultimas_24':     return ahora.getTime() - fechaPedido.getTime() <=  86400000;
    case 'ultimos_2_dias': return ahora.getTime() - fechaPedido.getTime() <= 2*86400000;
    case 'ultima_semana':  return ahora.getTime() - fechaPedido.getTime() <= 7*86400000;
    case 'este_mes':       return fechaPedido.getMonth() === ahora.getMonth() && fechaPedido.getFullYear() === ahora.getFullYear();
    case 'rango_fechas':
      if (!fechaInicio.value || !fechaFin.value || isFechaFinalInvalid.value) return true;
      const ini = new Date(fechaInicio.value).getTime();
      const fin = new Date(fechaFin.value).getTime();
      return fechaPedido.getTime() >= ini && fechaPedido.getTime() <= fin;
    default: return true;
  }
};

// Lista dinámica de estados
const estados = [
  'Pendiente',
  'En preparación',
  'Listo para servir',
  'Entregado',
  'Cancelado',
  'Pagado',
  'Modificado',
  'Rechazado'
];

// Props del componente
const props = defineProps<{
  orders: {
    id_pedido: number;
    fecha_hora_registro: string;
    estadopedido: { nombre_estado: string; color_codigo: string; };
    usuario_mesero: { id_usuario: number; nombre: string; };
    detallepedidos: {
      id_producto: number;
      cantidad: number;
      comentario: string;
      producto: { nombre: string; precio: number; };
    }[];
  }[];
}>();

const orders = ref(props.orders);
const selectedOrder = ref<number | null>(null);
const showResumen = ref(false);
const metodoPago = ref<'Efectivo'|'Tarjeta'|'QR'|''>('');
const showPagoModal = ref(false);

const abrirResumen = (id: number) => { selectedOrder.value = id; showResumen.value = true; };
const cerrarResumen = () => { selectedOrder.value = null; showResumen.value = false; };

const pedidoSeleccionado = computed(() =>
  orders.value.find(o => o.id_pedido === selectedOrder.value)
);

const marcarComoPagado = async () => {
  if (!selectedOrder.value || !metodoPago.value) return;
  try {
    const { data } = await axios.post(`/order/${selectedOrder.value}/pagar`, {
      metodo_pago: metodoPago.value
    });
    const pedido = orders.value.find(p => p.id_pedido === selectedOrder.value);
    if (pedido) pedido.estadopedido = data.nuevo_estado;
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
    if (pedido) pedido.estadopedido = data.nuevo_estado;
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

const totalPedido = (detalle: typeof props.orders[0]['detallepedidos']) =>
  detalle.reduce((sum, item) => sum + item.producto.precio * item.cantidad, 0).toFixed(2);

const redirectToCloseCash = () => { router.visit('/close-cash'); };

// Computed: filtrado incluyendo estado
const filteredOrders = computed(() =>
  orders.value.filter(p =>
    (!filtroNumero.value || p.id_pedido.toString().includes(filtroNumero.value)) &&
    (!filtroEstado.value || p.estadopedido.nombre_estado === filtroEstado.value) &&
    filtrarPorTiempo(p.fecha_hora_registro)
  )
);

// 👉 PAGINACIÓN
const currentPage = ref(1);
const itemsPerPage = 30;

const totalPages = computed(() =>
  Math.ceil(filteredOrders.value.length / itemsPerPage)
);

const paginatedOrders = computed(() => {
  const start = (currentPage.value - 1) * itemsPerPage;
  return filteredOrders.value.slice(start, start + itemsPerPage);
});

const goToPage = (page: number) => {
  if (page >= 1 && page <= totalPages.value) currentPage.value = page;
};
</script>

<template>
  <AppLayout>
    <Head title="Caja - Pedidos" />

    <div class="p-4 sm:p-6 text-[#4b3621] dark:text-white">
      <!-- Header -->
      <div class="flex justify-between items-center mb-4">
        <h1 class="text-2xl font-bold">Pedidos en Caja</h1>
        <button @click="redirectToCloseCash"
                class="bg-purple-600 hover:bg-purple-700 text-white text-sm px-4 py-2 rounded shadow">
          Cierre de caja
        </button>
      </div>

      <!-- FILTROS -->
      <div class="flex flex-wrap gap-2 mb-6 items-center">
        <input v-model="filtroNumero" type="text" placeholder="Buscar por #"
               class="border text-black rounded px-3 py-1.5 text-sm w-44"/>

        <select v-model="filtroTiempo"
                class="border text-black rounded px-3 py-1.5 text-sm w-44">
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

        <select v-model="filtroEstado"
                class="border text-black rounded px-3 py-1.5 text-sm w-44">
          <option value="">Todos los estados</option>
          <option v-for="estado in estados" :key="estado" :value="estado">
            {{ estado }}
          </option>
        </select>

        <div v-if="filtroTiempo === 'rango_fechas'" class="flex gap-2">
          <input type="date" v-model="fechaInicio" @change="validarFechas"
                 class="border text-black rounded px-3 py-1.5 text-sm"/>
          <input type="date" v-model="fechaFin" :min="fechaInicio||undefined"
                 @change="validarFechas"
                 class="border text-black rounded px-3 py-1.5 text-sm"/>
        </div>

        <button @click="() => {
                          filtroNumero=''; filtroTiempo=''; filtroEstado='';
                          fechaInicio=null; fechaFin=null;
                        }"
                class="text-sm bg-red-500 hover:bg-red-600 text-white rounded px-3 py-1.5">
          Limpiar filtros
        </button>
      </div>

      <!-- LISTADO PAGINADO -->
      <div v-if="paginatedOrders.length">
        <div v-for="order in paginatedOrders" :key="order.id_pedido"
             class="mb-4 p-4 rounded shadow border border-[#d8c6ad] dark:border-[#6c4f3c] relative overflow-hidden"
             :style="{ backgroundColor: order.estadopedido.color_codigo + '22' }">

          <div class="absolute top-0 left-0 h-full w-2"
               :style="{ backgroundColor: order.estadopedido.color_codigo }"></div>

          <div class="flex justify-between items-start text-sm text-black dark:text-white">
            <div>
              <p class="font-semibold">Pedido #{{ order.id_pedido }}</p>
              <p class="text-xs">{{ new Date(order.fecha_hora_registro).toLocaleString() }}</p>
              <p class="text-xs font-medium">Mesero: {{ order.usuario_mesero.nombre }}</p>
              <p class="text-xs font-medium mt-1">
                Total: {{ totalPedido(order.detallepedidos) }} Bs
              </p>
              <div class="flex items-center gap-2 mt-1">
                <span class="w-3 h-3 rounded-full"
                      :style="{ backgroundColor: order.estadopedido.color_codigo }"></span>
                <span class="px-2 py-0.5 rounded text-xs font-semibold text-white"
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
              <button v-if="order.estadopedido.nombre_estado!=='Pagado'"
                      @click="abrirPagoModal(order.id_pedido)"
                      class="bg-green-600 hover:bg-green-700 text-white text-xs px-3 py-1 rounded shadow">
                Marcar como pagado
              </button>
              <button v-else
                      @click="rehacerPago(order.id_pedido)"
                      class="bg-yellow-500 hover:bg-yellow-600 text-white text-xs px-3 py-1 rounded shadow">
                Marcar como no pagado
              </button>
            </div>
          </div>
        </div>
      </div>
      <p v-else class="text-sm text-gray-600">No hay pedidos disponibles.</p>

      <!-- PAGINACIÓN -->
      <div v-if="totalPages > 1" class="flex justify-center mt-6 gap-4 text-sm">
        <button @click="goToPage(currentPage - 1)" :disabled="currentPage===1"
                class="px-3 py-1 rounded border bg-white disabled:opacity-50">
          Anterior
        </button>
        <span>Página {{ currentPage }} de {{ totalPages }}</span>
        <button @click="goToPage(currentPage + 1)" :disabled="currentPage===totalPages"
                class="px-3 py-1 rounded border bg-white disabled:opacity-50">
          Siguiente
        </button>
      </div>

      <!-- ... resto de modales igual ... -->

    </div>
  </AppLayout>
</template>
