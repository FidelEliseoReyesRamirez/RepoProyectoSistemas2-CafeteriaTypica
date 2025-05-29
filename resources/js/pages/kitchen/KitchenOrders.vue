<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import { onMounted, ref, computed } from 'vue';
import axios, { AxiosError } from 'axios';

// Filtros
const filtroNumero = ref('');
const filtroEstado = ref('');
const filtroTiempo = ref('');
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
    case 'ultima_hora': return ahora.getTime() - fechaPedido.getTime() <= 3600000;
    case 'ultimas_2': return ahora.getTime() - fechaPedido.getTime() <= 7200000;
    case 'hoy': return fechaPedido.toDateString() === ahora.toDateString();
    case 'ultimas_24': return ahora.getTime() - fechaPedido.getTime() <= 86400000;
    case 'ultimos_2_dias': return ahora.getTime() - fechaPedido.getTime() <= 2 * 86400000;
    case 'ultima_semana': return ahora.getTime() - fechaPedido.getTime() <= 7 * 86400000;
    case 'este_mes': return fechaPedido.getMonth() === ahora.getMonth() && fechaPedido.getFullYear() === ahora.getFullYear();
    case 'rango_fechas':
      if (!fechaInicio.value || !fechaFin.value || isFechaFinalInvalid.value) return true;
      const ini = new Date(fechaInicio.value).getTime();
      const fin = new Date(fechaFin.value).getTime();
      return fechaPedido.getTime() >= ini && fechaPedido.getTime() <= fin;
    default: return true;
  }
};

// Pedidos
const pedidosActivos = ref<any[]>([]);
const pedidosCancelados = ref<any[]>([]);
const pedidoResumen = ref<any | null>(null);
const mostrarResumen = ref(false);

// Audio reactivo
const previousStates = ref<Record<number, string>>({});
const audioContext = new AudioContext();
const audioBuffers = new Map<string, AudioBuffer>();
const tiempoPendiente = ref<Record<number, number>>({});
const INTERVALO_MS = 3000;
const LIMITE_MS = 5 * 60 * 1000;
let primeraCarga = true;

const normalizarEstado = (estado: string) => {
  const mapa: Record<string, string> = { 'listo para servir': 'listo' };
  const estadoNormal = estado.toLowerCase().normalize("NFD").replace(/[\u0300-\u036f]/g, '').trim();
  return mapa[estadoNormal] ?? estadoNormal.replace(/\s+/g, '_');
};

const unlockAudioContext = () => {
  if (audioContext.state === 'suspended') {
    audioContext.resume();
  }
};

const cargarAudios = async () => {
  const estados = [
    'pendiente', 'en_preparacion', 'listo', 'entregado',
    'cancelado', 'pagado', 'modificado', 'rechazado', 'recordatorio',
  ];

  for (const estado of estados) {
    try {
      const response = await fetch(`/sounds/${estado}.mp3`);
      const arrayBuffer = await response.arrayBuffer();
      const buffer = await audioContext.decodeAudioData(arrayBuffer);
      audioBuffers.set(estado, buffer);
    } catch {
      console.warn(`No se pudo cargar el audio: ${estado}`);
    }
  }
};

const reproducirAudio = (estado: string) => {
  const buffer = audioBuffers.get(estado);
  if (buffer) {
    const source = audioContext.createBufferSource();
    source.buffer = buffer;
    source.connect(audioContext.destination);
    source.start(0);
  }
};

const cargarPedidos = async () => {
  const { data } = await axios.get('/api/kitchen-orders');
  pedidosActivos.value = data.activos.filter((p: any) =>
    ['Pendiente', 'Modificado', 'Listo para servir', 'En preparación']
      .includes(p.estadopedido.nombre_estado)
  );
  pedidosCancelados.value = data.cancelados;

  const todosLosPedidos = [
    ...data.activos,
    ...data.cancelados,
    ...(data.entregados ?? []),
    ...(data.rechazados ?? []),
    ...(data.pagados ?? []),
    ...(data.modificados ?? []),
  ];

  for (const pedido of todosLosPedidos) {
    const id = pedido.id_pedido;
    const actual = pedido.estadopedido.nombre_estado;
    const anterior = previousStates.value[id];
    const estadoKey = normalizarEstado(actual);

    if ((!anterior || anterior !== actual) && !primeraCarga) {
      reproducirAudio(estadoKey);
    }

    if (estadoKey === 'pendiente') {
      tiempoPendiente.value[id] = (tiempoPendiente.value[id] || 0) + INTERVALO_MS;
      if (tiempoPendiente.value[id] >= LIMITE_MS) {
        reproducirAudio('recordatorio');
        tiempoPendiente.value[id] = 0;
      }
    } else {
      delete tiempoPendiente.value[id];
    }

    previousStates.value[id] = actual;
  }

  primeraCarga = false;
};

const cambiarEstado = async (id: number, nuevoEstado: string) => {
  await axios.put(`/api/kitchen-orders/${id}/estado`, { estado: nuevoEstado });
  cargarPedidos();
};

const abrirResumen = (pedido: any) => {
  pedidoResumen.value = pedido;
  mostrarResumen.value = true;
};

const cerrarResumen = () => {
  pedidoResumen.value = null;
  mostrarResumen.value = false;
};

const generarPDF = async (pedidoId: number) => {
  try {
    const response = await axios.get(`/pedido/${pedidoId}/pdf`, { responseType: 'blob' });
    const url = window.URL.createObjectURL(new Blob([response.data], { type: 'application/pdf' }));
    const pdfWindow = window.open(url, '_blank');
    pdfWindow?.addEventListener('load', () => pdfWindow?.print());
  } catch (error) {
    console.error('Error generando PDF:', error);
  }
};

const mostrarModalRechazo = ref(false);
const comentarioRechazo = ref('');
const pedidoRechazoId = ref<number | null>(null);

const confirmarRechazo = (id: number) => {
  pedidoRechazoId.value = id;
  comentarioRechazo.value = '';
  mostrarModalRechazo.value = true;
};

const rechazarPedido = async () => {
  if (pedidoRechazoId.value !== null) {
    try {
      await axios.post(`/pedidos/${pedidoRechazoId.value}/rechazar`, { comentario: comentarioRechazo.value });
      mostrarModalRechazo.value = false;
      cargarPedidos();
    } catch (error) {
      const err = error as AxiosError;
      console.error('Error al rechazar el pedido:', err.response?.data ?? err.message);
      alert(JSON.stringify(err.response?.data ?? err.message));
    }
  }
};

const pedidosFiltrados = computed(() =>
  pedidosActivos.value.filter(p =>
    (!filtroNumero.value || p.id_pedido.toString().includes(filtroNumero.value)) &&
    (!filtroEstado.value || p.estadopedido.nombre_estado === filtroEstado.value) &&
    filtrarPorTiempo(p.fecha_hora_registro)
  )
);

onMounted(() => {
  document.addEventListener('click', unlockAudioContext, { once: true });
  cargarAudios();
  cargarPedidos();
  setInterval(cargarPedidos, INTERVALO_MS);
});
</script>



<template>
  <AppLayout>

    <Head title="Pedidos Cocina" />
    <div class="p-4 sm:p-6 w-full max-w-screen-xl mx-auto text-[#4b3621] dark:text-white">
      <h1 class="text-2xl sm:text-3xl font-bold mb-6">Pedidos Cocina</h1>

      <div class="flex flex-wrap gap-2 mb-6 items-center">
        <button @click="router.visit('/kitchen-orders/canceled')" class="px-3 py-1.5 text-sm text-white rounded shadow"
          style="background-color: #FF0000">
          Ped. Cancelados
        </button>
        <button @click="router.visit('/kitchen-orders/completed')" class="px-3 py-1.5 text-sm text-white rounded shadow"
          style="background-color: #800080">
          Ped. Finalizados
        </button>
        <button @click="router.visit('/kitchen-orders/delivered')" class="px-3 py-1.5 text-sm text-white rounded shadow"
          style="background-color: #0000FF">
          Ped. Entregados
        </button>
        <button @click="router.visit('/kitchen-orders/rejected')" class="px-3 py-1.5 text-sm text-white rounded shadow"
          style="background-color: #6a6362">
          Ped. Rechazados
        </button>

        <input v-model="filtroNumero" type="text" placeholder="Buscar por #"
          class="border text-black rounded px-3 py-1.5 text-sm w-44" />
        <select v-model="filtroEstado" class="border text-black rounded px-3 py-1.5 text-sm w-44">
          <option value="">Todos los estados</option>
          <option value="Pendiente">Pendiente</option>
          <option value="Modificado">Modificado</option>
          <option value="Listo para servir">Listo para servir</option>
          <option value="En preparación">En preparación</option>
        </select>
        <select v-model="filtroTiempo" class="border text-black rounded px-3 py-1.5 text-sm w-44">
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

        <div v-if="filtroTiempo === 'rango_fechas'" class="flex gap-2">
          <input type="date" v-model="fechaInicio" @change="validarFechas"
            class="border text-black rounded px-3 py-1.5 text-sm" />
          <input type="date" v-model="fechaFin" :min="fechaInicio || undefined" @change="validarFechas"
            class="border text-black rounded px-3 py-1.5 text-sm" />
        </div>

        <button
          @click="() => { filtroNumero = ''; filtroEstado = ''; filtroTiempo = ''; fechaInicio = null; fechaFin = null }"
          class="text-sm bg-red-500 hover:bg-red-600 text-white rounded px-3 py-1.5">
          Limpiar filtros
        </button>
      </div>

      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">

        <!-- Cada pedido es ahora un contenedor independiente que no afecta el tamaño de los demás -->
        <div v-for="(pedido, index) in pedidosActivos" :key="pedido.id_pedido"
          class="pedido-card rounded border relative overflow-visible p-4"
          :style="{ backgroundColor: pedido.estadopedido.color_codigo + '22', borderColor: pedido.estadopedido.color_codigo }">
          <div class="absolute left-0 top-0 h-full w-2" :style="{ backgroundColor: pedido.estadopedido.color_codigo }">
          </div>
          <div class="absolute right-0 top-0 h-full w-2" :style="{ backgroundColor: pedido.estadopedido.color_codigo }">
          </div>

          <div
            class="absolute -top-4 -left-4 text-black font-bold text-xl rounded-full h-10 w-10 flex items-center justify-center z-20"
            :style="{ backgroundColor: pedido.estadopedido.color_codigo }">
            {{ index + 1 }}
          </div>

          <div class="flex justify-between items-center mb-2">
            <h2 class="font-bold">Pedido #{{ pedido.id_pedido }}</h2>
            <span class="text-xs">{{ new Date(pedido.fecha_hora_registro).toLocaleString('es-BO') }}</span>
          </div>

          <p class="text-xs font-semibold mb-2">
            Estado:
            <span
              :class="['rounded px-2 py-0.5', pedido.estadopedido.nombre_estado === 'Pendiente' ? 'text-black' : 'text-white']"
              :style="{ backgroundColor: pedido.estadopedido.color_codigo }">
              {{ pedido.estadopedido.nombre_estado }}
            </span>
          </p>

          <div class="relative pr-1 mb-2">
            <ul class="text-sm divide-y divide-gray-300 dark:divide-gray-600 custom-scrollbar">
              <li v-for="detalle in pedido.detallepedidos" :key="detalle.id_detalle" class="py-1">
                {{ detalle.cantidad }} x {{ detalle.producto.nombre }}
                <span v-if="detalle.comentario" class="block text-xs italic">
                  {{ detalle.comentario }}
                </span>
                <!-- Resaltar los nuevos detalles -->
                <span v-if="pedido.nuevos_detalles.includes(detalle.producto.nombre)" class="text-xs text-red-500 ml-2">
                  (Nuevo)
                </span>
              </li>
            </ul>
          </div>

          <div class="flex flex-wrap gap-2 justify-center">
            <button v-if="pedido.estadopedido.nombre_estado !== 'En preparación'"
              @click="cambiarEstado(pedido.id_pedido, 'En preparación')"
              class="text-xs bg-[#FC4B08] hover:bg-[#FC4B08] text-white rounded px-2 py-1">
              Preparar
            </button>
            <button v-if="pedido.estadopedido.nombre_estado !== 'Listo para servir'"
              @click="cambiarEstado(pedido.id_pedido, 'Listo para servir')"
              class="text-xs bg-green-600 hover:bg-green-700 text-white rounded px-2 py-1">
              Listo
            </button>
            <button v-if="pedido.estadopedido.nombre_estado !== 'Entregado'"
              @click="cambiarEstado(pedido.id_pedido, 'Entregado')"
              class="text-xs bg-blue-600 hover:bg-blue-700 text-white rounded px-2 py-1">
              Entregado
            </button>
            <button v-if="pedido.estadopedido.nombre_estado !== 'Pendiente'"
              @click="cambiarEstado(pedido.id_pedido, 'Pendiente')"
              class="text-xs bg-[#FFB300] hover:bg-[#FFB300] text-white rounded px-2 py-1">
              Pendiente
            </button>
            <button v-if="pedido.estadopedido.nombre_estado !== 'Rechazado'" @click="confirmarRechazo(pedido.id_pedido)"
              class="text-xs bg-red-700 hover:bg-red-800 text-white rounded px-2 py-1">
              Rechazar
            </button>

            <button @click="abrirResumen(pedido)"
              class="text-xs bg-gray-600 hover:bg-gray-700 text-white rounded px-2 py-1">
              Detalles
            </button>
            <button @click="generarPDF(pedido.id_pedido)"
              class="text-xs bg-[#800080] hover:bg-[#9b4dff]  text-white rounded px-2 py-1">
              Imprimir
            </button>
          </div>
        </div>
      </div>

      <div v-if="mostrarResumen && pedidoResumen"
        class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4">
        <div class="bg-white dark:bg-[#2c211b] p-6 rounded-lg shadow-xl w-full max-w-md">
          <h2 class="text-lg font-bold mb-4">Resumen del Pedido #{{ pedidoResumen.id_pedido }}</h2>
          <div class="relative mb-4 max-h-64 overflow-y-auto pr-2">
            <ul class="divide-y divide-[#c5a880] dark:divide-[#8c5c3b]">
              <li v-for="item in pedidoResumen.detallepedidos" :key="item.id_detalle" class="py-2">
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
          </div>
          <div class="flex justify-end mt-4">
            <button @click="cerrarResumen"
              class="px-4 py-2 rounded border hover:bg-neutral-100 dark:hover:bg-[#3a2e26]">
              Cerrar
            </button>
          </div>
        </div>
      </div>
    </div>
  </AppLayout>
  <div v-if="mostrarModalRechazo" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50">
    <div class="bg-white dark:bg-[#2c211b] p-6 rounded-lg w-full max-w-md">
      <h2 class="text-lg font-bold mb-2">Rechazar pedido</h2>
      <p class="text-sm mb-2">Este pedido será rechazado. El mesero será notificado.</p>
      <textarea v-model="comentarioRechazo" class="w-full border p-2 rounded text-sm" rows="3"
        placeholder="Motivo del rechazo (opcional)"></textarea>
      <div class="mt-4 flex justify-end gap-2">
        <button @click="mostrarModalRechazo = false" class="px-3 py-1 rounded border">Cancelar</button>
        <button @click="rechazarPedido" class="px-3 py-1 bg-red-700 text-white rounded">Rechazar</button>
      </div>
    </div>
  </div>

</template>

<style scoped>
button {
  transition: background-color 0.2s ease-in-out;
}

/* Custom Scrollbar */
.custom-scrollbar::-webkit-scrollbar {
  width: 6px;
  height: 6px;
}

.custom-scrollbar::-webkit-scrollbar-thumb {
  background-color: rgba(0, 0, 0, 0.3);
  border-radius: 10px;
}

.custom-scrollbar::-webkit-scrollbar-track {
  background: transparent;
}

.grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
  /* Ajuste automático de columnas */
  gap: 1rem;
  grid-auto-rows: auto;
  /* La altura se ajustará automáticamente según el contenido */
  align-items: start;
  /* Asegura que cada pedido tenga su propia altura */
}

.pedido-card {
  display: flex;
  flex-direction: column;
  justify-content: flex-start;
  background-color: #ffffff;
  height: auto;
  /* La altura será automática según el contenido */
}
</style>
