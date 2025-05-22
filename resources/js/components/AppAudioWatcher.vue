<script setup lang="ts">
import { ref, onMounted, onUnmounted } from 'vue';

const previousStates = ref<Record<number, string>>({});
const audioContext = new AudioContext();
const audioBuffers = new Map<string, AudioBuffer>();

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
            const anterior = previousStates.value[pedido.id_pedido];
            const actual = pedido.estadopedido.nombre_estado;

            if (anterior && anterior !== actual) {
                const estadoKey = normalizarEstado(actual);
                reproducirAudio(estadoKey);
            }

            previousStates.value[pedido.id_pedido] = actual;
        }
    } catch (error) {
        console.error('Error actualizando pedidos:', error);
    }
};

let intervalo: number;

onMounted(() => {
    document.addEventListener('click', unlockAudioContext, { once: true });
    cargarAudios();
    actualizarPedidos();
    intervalo = setInterval(actualizarPedidos, 1000);
});

onUnmounted(() => {
    clearInterval(intervalo);
});
</script>

<template>
    <!-- Este componente no tiene UI visible -->
</template>
