<template>
    <AppLayout>
        <Head title="Configuración del sistema" />

        <div class="p-4 flex flex-col gap-6 text-[#4b3621] dark:text-white">
            <div class="rounded-xl p-4 border border-[#c5a880] dark:border-[#8c5c3b] space-y-6">
                <!-- Tiempo para cancelar y editar -->
                <div class="grid gap-4 md:grid-cols-2">
                    <div>
                        <label class="block text-sm font-medium mb-1">Minutos para cancelar pedidos (Para meseros)</label>
                        <input type="number" v-model.number="tiempoCancelacion" :disabled="sinLimiteCancelacion" min="0"
                            class="w-full rounded border px-3 py-2 text-sm dark:bg-[#2c211b] dark:text-white border-[#c5a880] dark:border-[#8c5c3b]"
                            @keypress="validarNumero" />
                        <div class="mt-2">
                            <label class="inline-flex items-center gap-2 text-sm">
                                <input type="checkbox" v-model="sinLimiteCancelacion" />
                                Sin límite
                            </label>
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium mb-1">Minutos para editar pedidos (Para meseros)</label>
                        <input type="number" v-model.number="tiempoEdicion" :disabled="sinLimiteEdicion" min="0"
                            class="w-full rounded border px-3 py-2 text-sm dark:bg-[#2c211b] dark:text-white border-[#c5a880] dark:border-[#8c5c3b]"
                            @keypress="validarNumero" />
                        <div class="mt-2">
                            <label class="inline-flex items-center gap-2 text-sm">
                                <input type="checkbox" v-model="sinLimiteEdicion" />
                                Sin límite
                            </label>
                        </div>
                    </div>
                </div>

                <!-- Estados -->
                <div class="flex flex-col gap-2">
                    <h2 class="text-base font-bold">Estados</h2>
                    <p class="text-sm text-[#6b4f30] dark:text-gray-300">
                        Define en qué estados se puede editar o cancelar un pedido. Estos límites aplican solo para meseros.
                        <strong>El administrador puede hacerlo sin restricciones.</strong>
                    </p>
                    <div class="grid gap-4 md:grid-cols-2">
                        <div v-for="estado in estados" :key="estado.estado"
                            class="border p-3 rounded-lg dark:bg-[#1d1b16] border-[#c5a880] dark:border-[#8c5c3b]">
                            <p class="font-semibold text-sm mb-2">{{ estado.estado }}</p>
                            <div class="flex gap-4">
                                <label class="inline-flex items-center gap-1 text-sm">
                                    <input type="checkbox" v-model="estado.puede_cancelar"
                                        class="form-checkbox h-4 w-4 text-green-600 border-[#c5a880] dark:border-[#8c5c3b]" />
                                    Cancelar
                                </label>
                                <label class="inline-flex items-center gap-1 text-sm">
                                    <input type="checkbox" v-model="estado.puede_editar"
                                        class="form-checkbox h-4 w-4 text-blue-600 border-[#c5a880] dark:border-[#8c5c3b]" />
                                    Editar
                                </label>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="text-center">
                    <button @click="guardarConfig"
                        class="bg-green-600 hover:bg-green-700 text-white px-6 py-2 rounded shadow text-sm">
                        Guardar configuración
                    </button>
                </div>

                <!-- Horarios de atención -->
                <div class="flex flex-col gap-2">
                    <h2 class="text-base font-bold">Horarios de atención</h2>
                    <p class="text-sm text-[#6b4f30] dark:text-gray-300">Define los días y rangos horarios en los que se pueden registrar pedidos.</p>
                    <div class="space-y-4">
                        <div v-for="horario in horarios" :key="horario.dia"
                            class="flex flex-col sm:flex-row items-center justify-between gap-4 border p-4 rounded-lg dark:bg-[#1d1b16] border-[#c5a880] dark:border-[#8c5c3b]">
                            <p class="font-semibold text-sm w-32">{{ horario.dia }}</p>
                            <div class="flex items-center gap-2">
                                <label class="text-sm">Inicio:</label>
                                <input type="time" v-model="horario.hora_inicio"
                                    class="rounded border px-2 py-1 text-sm dark:bg-[#2c211b] dark:text-white border-[#c5a880] dark:border-[#8c5c3b]" />
                            </div>
                            <div class="flex items-center gap-2">
                                <label class="text-sm">Fin:</label>
                                <input type="time" v-model="horario.hora_fin"
                                    class="rounded border px-2 py-1 text-sm dark:bg-[#2c211b] dark:text-white border-[#c5a880] dark:border-[#8c5c3b]" />
                            </div>
                        </div>
                    </div>

                    <div class="text-center">
                        <button @click="guardarHorarios"
                            class="bg-yellow-600 hover:bg-yellow-700 text-white px-6 py-2 rounded shadow text-sm">
                            Guardar horarios
                        </button>
                    </div>
                </div>
            </div>

            <div v-if="mostrarModal" class="fixed inset-0 bg-black/40 z-50 flex items-center justify-center p-4">
                <div class="bg-white dark:bg-[#2c211b] p-6 rounded-lg shadow-xl w-full max-w-md">
                    <h2 class="text-lg font-bold mb-4">Aviso</h2>
                    <p class="text-sm mb-4">{{ mensajeModal }}</p>
                    <div class="flex justify-end">
                        <button @click="mostrarModal = false"
                            class="bg-green-600 text-white px-4 py-2 rounded hover:bg-green-700">
                            Aceptar
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<script setup lang="ts">
import { ref, watch, onMounted } from 'vue'
import { Head, usePage, router } from '@inertiajs/vue3'
import AppLayout from '@/layouts/AppLayout.vue'
import axios from 'axios'
interface Horario {
  dia: string;
  hora_inicio: string;
  hora_fin: string;
}



const page = usePage() as any
const config = page.props.config

const tiempoCancelacion = ref<number>(config?.tiempo_cancelacion_minutos ?? 5)
const tiempoEdicion = ref<number>(config?.tiempo_edicion_minutos ?? 10)
const estados = ref(config?.estados ?? [])
console.log('Nombres de los estados recibidos desde la configuración:');
estados.value.forEach((estado: { estado: any; }) => {
    console.log(estado.estado); // Suponiendo que 'estado' tiene la propiedad 'estado' que es el nombre del estado
});



const diasSemana = ['Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado', 'Domingo']

const horarios = ref<Horario[]>([])


const cargarHorarios = async () => {
    try {
        const { data } = await axios.get('/config/horarios')
        horarios.value = diasSemana.map(dia => {
            const encontrado = data.find((h: any) => h.dia === dia)
            return {
                dia,
                hora_inicio: encontrado ? encontrado.hora_inicio.slice(0, 5) : '08:00',
                hora_fin: encontrado ? encontrado.hora_fin.slice(0, 5) : (dia === 'Domingo' ? '14:00' : '20:00'),
            }
        })
    } catch {
        horarios.value = diasSemana.map(dia => ({
            dia,
            hora_inicio: '08:00',
            hora_fin: dia === 'Domingo' ? '14:00' : '20:00',
        }))
    }
}

onMounted(cargarHorarios)

const sinLimiteCancelacion = ref(tiempoCancelacion.value === 0)
const sinLimiteEdicion = ref(tiempoEdicion.value === 0)

const mostrarModal = ref(false)
const mensajeModal = ref('')

watch(sinLimiteCancelacion, (val) => {
    tiempoCancelacion.value = val ? 0 : 5
})
watch(sinLimiteEdicion, (val) => {
    tiempoEdicion.value = val ? 0 : 10
})

const validarNumero = (event: KeyboardEvent) => {
    if (!/^[0-9]$/.test(event.key)) {
        event.preventDefault()
    }
}

const guardarConfig = () => {
    router.post('/configuracion', {
        tiempo_cancelacion_minutos: tiempoCancelacion.value,
        tiempo_edicion_minutos: tiempoEdicion.value,
        estados: estados.value,
    }, {
        onSuccess: () => {
            mensajeModal.value = 'Configuración guardada correctamente'
            mostrarModal.value = true
        },
        onError: () => {
            mensajeModal.value = 'Ocurrió un error al guardar la configuración.'
            mostrarModal.value = true
        }
    })
}
const validarHorarios = (): boolean => {
    for (const h of horarios.value) {
        if (h.hora_inicio >= h.hora_fin) {
            mensajeModal.value = `La hora de inicio debe ser menor que la hora de fin en "${h.dia}".`
            mostrarModal.value = true
            return false
        }
    }
    return true
}
const guardarHorarios = async () => {
    if (!validarHorarios()) return

    try {
        const horariosFormateados = horarios.value.map(h => ({
            dia: h.dia,
            hora_inicio: h.hora_inicio + ':00',
            hora_fin: h.hora_fin + ':00',
        }))

        await axios.post('/config/horarios', { horarios: horariosFormateados })

        mensajeModal.value = 'Horarios actualizados correctamente'
        mostrarModal.value = true
    } catch {
        mensajeModal.value = 'Ocurrió un error al guardar los horarios.'
        mostrarModal.value = true
    }
}

</script>
