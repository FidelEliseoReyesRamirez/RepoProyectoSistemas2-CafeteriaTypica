<script setup lang="ts">
import { ref } from 'vue'
import { router, Head, Link } from '@inertiajs/vue3'
import AppLayout from '@/layouts/AppLayout.vue'

const props = defineProps<{
  logs: any,
  usuarios: { id_usuario: number, nombre: string }[],
  acciones: string[],
  filtros: {
    usuario_id?: string,
    accion?: string,
    fecha_inicio?: string,
    fecha_fin?: string,
    hora_inicio?: string,
    hora_fin?: string
  }
}>()

const filtroUsuario = ref(props.filtros.usuario_id || '')
const filtroAccion = ref(props.filtros.accion || '')
const filtroFechaInicio = ref(props.filtros.fecha_inicio || '')
const filtroFechaFin = ref(props.filtros.fecha_fin || '')
const filtroHoraInicio = ref(props.filtros.hora_inicio || '')
const filtroHoraFin = ref(props.filtros.hora_fin || '')
const errorFecha = ref(false)
const errorHora = ref(false)

const aplicarFiltros = () => {
  errorFecha.value = false
  errorHora.value = false

  if (filtroFechaInicio.value && filtroFechaFin.value && filtroFechaFin.value < filtroFechaInicio.value) {
    errorFecha.value = true
    return
  }

  if (filtroHoraInicio.value && filtroHoraFin.value && filtroHoraFin.value < filtroHoraInicio.value) {
    errorHora.value = true
    return
  }

  router.get('/audit', {
    usuario_id: filtroUsuario.value || undefined,
    accion: filtroAccion.value || undefined,
    fecha_inicio: filtroFechaInicio.value || undefined,
    fecha_fin: filtroFechaFin.value || undefined,
    hora_inicio: filtroHoraInicio.value || undefined,
    hora_fin: filtroHoraFin.value || undefined,
  }, {
    preserveState: true,
    preserveScroll: true
  })
}
const exportarExcel = () => {
  const params = new URLSearchParams();
  if (filtroUsuario.value) params.set('usuario_id', filtroUsuario.value);
  if (filtroAccion.value) params.set('accion', filtroAccion.value);
  if (filtroFechaInicio.value) params.set('fecha_inicio', filtroFechaInicio.value);
  if (filtroFechaFin.value) params.set('fecha_fin', filtroFechaFin.value);
  window.location.href = `/exportar-auditoria?${params.toString()}`;
};

const limpiarFiltros = () => {
  filtroUsuario.value = ''
  filtroAccion.value = ''
  filtroFechaInicio.value = ''
  filtroFechaFin.value = ''
  filtroHoraInicio.value = ''
  filtroHoraFin.value = ''
  errorFecha.value = false
  errorHora.value = false
  aplicarFiltros()
}
</script>

<template>
  <AppLayout>

    <Head title="Auditoría del sistema" />

    <div class="p-4 text-[#4b3621] dark:text-white">
      <div class="rounded-xl p-4 border border-[#c5a880] dark:border-[#8c5c3b] space-y-6 bg-white dark:bg-[#1d1b16]">
        <h1 class="text-2xl font-bold text-[#593E25] dark:text-[#d9a679]">Auditoría del sistema</h1>

        <!-- FILTROS -->
        <div class="grid sm:grid-cols-3 gap-4">
          <div>
            <label class="text-sm">Usuario</label>
            <select v-model="filtroUsuario" @change="aplicarFiltros"
              class="w-full border px-3 py-2 rounded text-sm text-black">
              <option value="">Todos los usuarios</option>
              <option v-for="u in props.usuarios" :key="u.id_usuario" :value="u.id_usuario">{{ u.nombre }}</option>
            </select>
          </div>

          <div>
            <label class="text-sm">Acción</label>
            <select v-model="filtroAccion" @change="aplicarFiltros"
              class="w-full border px-3 py-2 rounded text-sm text-black">
              <option value="">Todas las acciones</option>
              <option v-for="accion in props.acciones" :key="accion" :value="accion">{{ accion }}</option>
            </select>
          </div>

          <div class="flex items-end gap-2">
            <button @click="limpiarFiltros"
              class="bg-red-500 hover:bg-red-600 text-white px-4 py-2 rounded shadow text-sm w-32">
              Limpiar
            </button>

            <button @click="exportarExcel"
              class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded shadow text-sm w-32">
              Exportar Excel
            </button>
          </div>


          <div>
            <label class="text-sm">Fecha inicio</label>
            <input v-model="filtroFechaInicio" @change="aplicarFiltros" type="date"
              class="w-full border px-3 py-2 rounded text-sm text-black" />
          </div>

          <div>
            <label class="text-sm">Fecha fin</label>
            <input v-model="filtroFechaFin" @change="aplicarFiltros" type="date"
              class="w-full border px-3 py-2 rounded text-sm text-black" :min="filtroFechaInicio" />
          </div>

          <div v-if="errorFecha" class="col-span-3 text-red-600 text-sm font-semibold">
            La fecha final no puede ser anterior a la fecha de inicio.
          </div>
          <div v-if="errorHora" class="col-span-3 text-red-600 text-sm font-semibold">
            La hora final no puede ser anterior a la hora de inicio.
          </div>

          <div>
            <label class="text-sm">Hora inicio</label>
            <input v-model="filtroHoraInicio" @change="aplicarFiltros" type="time"
              class="w-full border px-3 py-2 rounded text-sm text-black" />
          </div>

          <div>
            <label class="text-sm">Hora fin</label>
            <input v-model="filtroHoraFin" @change="aplicarFiltros" type="time"
              class="w-full border px-3 py-2 rounded text-sm text-black" />
          </div>
        </div>

        <!-- TABLA -->
        <div class="overflow-x-auto">
          <table class="min-w-full text-xs sm:text-sm">
            <thead class="bg-neutral-100 dark:bg-neutral-800 text-left">
              <tr>

                <th class="px-3 py-2">ID Usuario</th>
                <th class="px-3 py-2">Acción</th>
                <th class="px-3 py-2">Descripción</th>
                <th class="px-3 py-2">Fecha y Hora</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="log in props.logs.data" :key="log.id_log" class="border-t dark:border-neutral-700">

                <td class="px-3 py-2">{{ log.id_usuario }}</td>
                <td class="px-3 py-2">{{ log.accion }}</td>
                <td class="px-3 py-2">{{ log.descripcion }}</td>
                <td class="px-3 py-2">{{ new Date(log.fecha_hora).toLocaleString() }}</td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- Paginación -->
        <div class="flex justify-center gap-4 mt-4">
          <Link v-if="props.logs.prev_page_url" :href="props.logs.prev_page_url"
            class="text-xs sm:text-sm px-4 py-2 rounded bg-[#a47148] text-white hover:bg-[#8c5c3b]">Anterior</Link>
          <Link v-if="props.logs.next_page_url" :href="props.logs.next_page_url"
            class="text-xs sm:text-sm px-4 py-2 rounded bg-[#a47148] text-white hover:bg-[#8c5c3b]">Siguiente</Link>
        </div>
      </div>
    </div>
  </AppLayout>
</template>
