<script setup lang="ts">
import { ref, onMounted, onUnmounted } from 'vue';

const previousStates = ref<Record<number, string>>({});
let audioBuffer: AudioBuffer | null = null;
const audioContext = new AudioContext();

// Desbloquear el contexto de audio con el primer clic
const unlockAudioContext = () => {
    if (audioContext.state === 'suspended') {
        audioContext.resume();
    }
};

// Cargar el audio una sola vez
const cargarAudio = async () => {
    try {
        const response = await fetch('/sounds/listo.mp3');
        const arrayBuffer = await response.arrayBuffer();
        audioBuffer = await audioContext.decodeAudioData(arrayBuffer);
    } catch (error) {
        console.error('Error cargando el audio:', error);
    }
};

const reproducirAudio = () => {
    if (audioBuffer) {
        const source = audioContext.createBufferSource();
        source.buffer = audioBuffer;
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

            if (actual === 'Listo para servir' && anterior && anterior !== actual) {
                reproducirAudio();
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
    cargarAudio();
    actualizarPedidos(); // inicial
    intervalo = setInterval(actualizarPedidos, 1000);
});

onUnmounted(() => {
    clearInterval(intervalo);
});
</script>

<template>
  <!-- Este componente no renderiza nada visible -->
</template>

