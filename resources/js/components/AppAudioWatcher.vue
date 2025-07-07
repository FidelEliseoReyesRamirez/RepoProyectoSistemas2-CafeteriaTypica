<script setup lang="ts">
import { ref, onMounted, onUnmounted } from 'vue';
import { usePage } from '@inertiajs/vue3';
import type { PageProps } from '@/types';

const page = usePage<PageProps>();
const user = page.props.auth?.user;

const previousSnapshots = ref<Record<number, string>>({});
const audioContext = new AudioContext();
const audioBuffers = new Map<string, AudioBuffer>();
const tiempoPorPedido = ref<Record<number, number>>({});

const INTERVALO_MS = 1000;
const LIMITE_MS = 5 * 60 * 1000;

let primeraCarga = true;

const normalizarEstado = (estado: string) => {
    const mapa: Record<string, string> = {
        'listo para servir': 'listo',
    };
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
            console.warn(`No se pudo cargar el audio de estado: ${estado}`);
        }
    }
};

const reproducirAudio = (estado: string) => {
    const buffer = audioBuffers.get(estado);
    if (buffer) {
        console.log(`[${new Date().toISOString()}] SONIDO: ${estado}`);
        const source = audioContext.createBufferSource();
        source.buffer = buffer;
        source.connect(audioContext.destination);
        source.start(0);
    }
};

// Snapshot = estado + detalles ordenados
const snapshotPedido = (pedido: any): string => {
    const estado = pedido.estadopedido?.nombre_estado ?? '';
    const detalles = (pedido.detallepedidos ?? [])
        .map((d: any) => `${d.id_producto}-${d.cantidad}-${d.comentario ?? ''}`)
        .sort()
        .join('|');
    return `${estado}|${detalles}`;
};

const actualizarPedidos = async () => {
    try {
        const response = await fetch('/api/my-orders');
        if (!response.ok || !response.headers.get('content-type')?.includes('application/json')) {
            console.warn('[AppAudioWatcher] Respuesta inválida, posiblemente HTML (no autenticado)');
            return;
        }

        const data = await response.json();
        for (const pedido of data.orders) {
            const id = pedido.id_pedido;
            const snapshot = snapshotPedido(pedido);
            const anterior = previousSnapshots.value[id];

            const estadoKey = normalizarEstado(pedido.estadopedido.nombre_estado);

            const debeSonar =
                (!anterior || anterior !== snapshot) &&
                !primeraCarga &&
                ['pendiente', 'modificado', 'rechazado', 'cancelado', 'listo', 'en_preparacion', 'entregado', 'pagado'].includes(estadoKey);

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
    } catch (error) {
        console.error('Error actualizando pedidos:', error);
    }
};

let intervalo: ReturnType<typeof setInterval>;

onMounted(() => {
    if (!user || ![1, 2].includes(user.id_rol)) return;
    document.addEventListener('click', unlockAudioContext, { once: true });
    cargarAudios();
    actualizarPedidos();
    intervalo = setInterval(actualizarPedidos, INTERVALO_MS) as unknown as number;
});

onUnmounted(() => {
    clearInterval(intervalo);
});
</script>

<template></template>
