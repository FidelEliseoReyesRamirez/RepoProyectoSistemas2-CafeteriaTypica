<script setup lang="ts">
import { ref, onMounted, onUnmounted } from 'vue';

const previousStates = ref<Record<number, string>>({});
const audioContext = new AudioContext();
const audioBuffers = new Map<string, AudioBuffer>();
const tiempoPendiente = ref<Record<number, number>>({});

const INTERVALO_MS = 1000;
const LIMITE_MS = 5 * 60 * 1000; // Para prueba: 10 * 1000

let primeraCarga = true;

// Normaliza el nombre del estado
const normalizarEstado = (estado: string) => {
    const mapa: Record<string, string> = {
        'listo para servir': 'listo',
    };

    const estadoNormal = estado.toLowerCase().normalize("NFD").replace(/[\u0300-\u036f]/g, '').trim();

    return mapa[estadoNormal] ?? estadoNormal.replace(/\s+/g, '_');
};

// Desbloquea el contexto de audio
const unlockAudioContext = () => {
    if (audioContext.state === 'suspended') {
        audioContext.resume();
    }
};

// Carga todos los audios
const cargarAudios = async () => {
    const estados = [
        'pendiente',
        'en_preparacion',
        'listo',
        'entregado',
        'cancelado',
        'pagado',
        'modificado',
        'rechazado',
        'recordatorio',
    ];

    for (const estado of estados) {
        try {
            const response = await fetch(`/sounds/${estado}.mp3`);
            const arrayBuffer = await response.arrayBuffer();
            const buffer = await audioContext.decodeAudioData(arrayBuffer);
            audioBuffers.set(estado, buffer);
        } catch (error) {
            console.warn(`No se pudo cargar el audio de estado: ${estado}`);
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

const actualizarPedidos = async () => {
    try {
        const response = await fetch('/api/my-orders');
        if (!response.ok) return;

        const data = await response.json();
        for (const pedido of data.orders) {
            const id = pedido.id_pedido;
            const actual = pedido.estadopedido.nombre_estado;
            const anterior = previousStates.value[id];
            const estadoKey = normalizarEstado(actual);

            // Reproducir sonido solo si no es la primera carga
            if ((!anterior || anterior !== actual) && !primeraCarga) {
                reproducirAudio(estadoKey);
            }

            // Si está en pendiente, acumula tiempo
            if (estadoKey === 'pendiente') {
                tiempoPendiente.value[id] = (tiempoPendiente.value[id] || 0) + INTERVALO_MS;

                // Si superó el límite de tiempo, sonar recordatorio y reiniciar
                if (tiempoPendiente.value[id] >= LIMITE_MS) {
                    reproducirAudio('recordatorio');
                    tiempoPendiente.value[id] = 0;
                }
            } else {
                // Si no está en pendiente, limpiar temporizador
                delete tiempoPendiente.value[id];
            }

            // Guardar estado actual
            previousStates.value[id] = actual;
        }

        // Ya no es primera carga
        primeraCarga = false;

    } catch (error) {
        console.error('Error actualizando pedidos:', error);
    }
};

let intervalo: number;

onMounted(() => {
    document.addEventListener('click', unlockAudioContext, { once: true });
    cargarAudios();
    actualizarPedidos();
    intervalo = setInterval(actualizarPedidos, INTERVALO_MS);
});

onUnmounted(() => {
    clearInterval(intervalo);
});
</script>

<template>
</template>
