<script setup lang="ts">
import { ref, onMounted, onUnmounted } from 'vue';

const previousSnapshots = ref<Record<number, string>>({});
const audioContext = new AudioContext();
const audioBuffers = new Map<string, AudioBuffer>();
const tiempoPorPedido = ref<Record<number, number>>({});

const INTERVALO_MS = 10000;
const LIMITE_MS = 3 * 60 * 1000;
let primeraCarga = true;

// Función para normalizar estado
const normalizarEstado = (estado: string) => {
  return estado.toLowerCase().normalize("NFD").replace(/[\u0300-\u036f]/g, '').trim().replace(/\s+/g, '_');
};

// Desbloquear contexto de audio
const unlockAudioContext = () => {
  if (audioContext.state === 'suspended') {
    audioContext.resume();
  }
};

// Cargar sonidos
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

// Reproducir sonido según estado
const reproducirAudio = (estado: string) => {
  const buffer = audioBuffers.get(estado);
  if (buffer) {
    const source = audioContext.createBufferSource();
    source.buffer = buffer;
    source.connect(audioContext.destination);
    source.start(0);
  }
};

// Crear snapshot del pedido
const snapshotPedido = (pedido: any): string => {
  const estado = pedido.estadopedido?.nombre_estado ?? '';
  const detalles = (pedido.detallepedidos ?? [])
    .map((d: any) => `${d.id_producto}-${d.cantidad}-${d.comentario ?? ''}`)
    .sort()
    .join('|');
  return `${estado}|${detalles}`;
};

// Actualizar pedidos
const actualizarPedidos = async () => {
  try {
    const response = await fetch('/api/kitchen-orders');
    if (!response.ok) return;

    const data = await response.json();
    const todos = [...(data.activos ?? []), ...(data.modificados ?? [])];

    for (const pedido of todos) {
      const id = pedido.id_pedido;
      const estado = pedido.estadopedido?.nombre_estado ?? '';
      const estadoKey = normalizarEstado(estado);
      const snapshot = snapshotPedido(pedido);
      const anterior = previousSnapshots.value[id];

      const debeSonar =
        (!anterior || anterior !== snapshot) &&
        !primeraCarga &&
        ['pendiente', 'modificado'].includes(estadoKey);

      if (debeSonar) {
        reproducirAudio(estadoKey);
      }

      if (['pendiente', 'modificado'].includes(estadoKey)) {
        tiempoPorPedido.value[id] = (tiempoPorPedido.value[id] || 0) + INTERVALO_MS;
        if (tiempoPorPedido.value[id] >= LIMITE_MS) {
          reproducirAudio('recordatorio');
          tiempoPorPedido.value[id] = 0;
        }
      } else {
        delete tiempoPorPedido.value[id];
      }

      previousSnapshots.value[id] = snapshot;
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
