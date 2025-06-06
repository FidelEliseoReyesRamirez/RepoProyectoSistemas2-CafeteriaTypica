<script setup lang="ts">
import { ref, onMounted, onUnmounted } from 'vue';

const previousStates = ref<Record<number, string>>({});
const audioContext = new AudioContext();
const audioBuffers = new Map<string, AudioBuffer>();
const tiempoPendiente = ref<Record<number, number>>({});

const INTERVALO_MS = 10000;
const LIMITE_MS = 3 * 60 * 1000; 
let primeraCarga = true;

const normalizarEstado = (estado: string) => {
  return estado.toLowerCase().normalize("NFD").replace(/[\u0300-\u036f]/g, '').trim().replace(/\s+/g, '_');
};

const unlockAudioContext = () => {
  if (audioContext.state === 'suspended') {
    audioContext.resume();
  }
};

const cargarAudios = async () => {
  const estados = ['pendiente', 'modificado', 'recordatorio'];
  for (const estado of estados) {
    try {
      const response = await fetch(`/sounds/${estado}.mp3`);
      const arrayBuffer = await response.arrayBuffer();
      const buffer = await audioContext.decodeAudioData(arrayBuffer);
      audioBuffers.set(estado, buffer);
    } catch {}
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
    const response = await fetch('/api/kitchen-orders');
    if (!response.ok) return;

    const data = await response.json();
    const todos = [...(data.activos ?? []), ...(data.modificados ?? [])];

    for (const pedido of todos) {
      const id = pedido.id_pedido;
      const actual = pedido.estadopedido.nombre_estado;
      const anterior = previousStates.value[id];
      const estadoKey = normalizarEstado(actual);

      const debeSonar =
        (!anterior || anterior !== actual) &&
        !primeraCarga &&
        ['pendiente', 'modificado'].includes(estadoKey);

      if (debeSonar) {
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
  } catch {}
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

<template></template>
