<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import { ref, computed, onMounted, watch } from 'vue';

interface Producto {
    id_producto: number;
    nombre: string;
    precio: number;
    imagen: string;
    disponibilidad: boolean;
    categorium?: { nombre: string };
}

const props = defineProps<{ productos: Producto[] }>();

const carrito = ref<{ id_producto: number; nombre: string; precio: number; cantidad: number; comentario: string }[]>([]);

// Leer desde localStorage al montar
onMounted(() => {
    const data = localStorage.getItem('carrito_pedido');
    if (data) {
        try {
            carrito.value = JSON.parse(data);
        } catch (e) {
            console.error('Error al cargar carrito local:', e);
            localStorage.removeItem('carrito_pedido');
        }
    }
});

// Guardar automáticamente al cambiar
watch(carrito, (nuevo) => {
    localStorage.setItem('carrito_pedido', JSON.stringify(nuevo));
}, { deep: true });

// Agregar producto
const agregarProducto = (producto: Producto) => {
    const existente = carrito.value.find(p => p.id_producto === producto.id_producto);
    if (existente) {
        existente.cantidad++;
    } else {
        carrito.value.push({
            id_producto: producto.id_producto,
            nombre: producto.nombre,
            precio: producto.precio,
            cantidad: 1,
            comentario: ''
        });
    }
};

// Quitar producto
const quitarProducto = (id: number) => {
    carrito.value = carrito.value.filter(p => p.id_producto !== id);
};

// Total
const total = computed(() =>
    carrito.value.reduce((sum, p) => sum + p.precio * p.cantidad, 0)
);

// Enviar pedido
const enviarPedido = () => {
    if (carrito.value.length === 0) return;

    router.post('/order', {
        items: carrito.value.map(p => ({
            id_producto: p.id_producto,
            cantidad: p.cantidad,
            comentario: p.comentario,
        }))
    }, {
        onSuccess: () => {
            carrito.value = [];
            localStorage.removeItem('carrito_pedido');
        }
    });
};

// Cancelar pedido
const cancelarPedido = () => {
    carrito.value = [];
    localStorage.removeItem('carrito_pedido');
};

// Filtro
const filtro = ref('');

const productosAgrupados = computed(() => {
    const grupos: Record<string, Producto[]> = {};
    const termino = filtro.value.toLowerCase();

    for (const p of props.productos) {
        if (
            !termino ||
            p.nombre.toLowerCase().includes(termino) ||
            (p.categorium?.nombre ?? '').toLowerCase().includes(termino)
        ) {
            const cat = p.categorium?.nombre ?? 'Sin categoría';
            if (!grupos[cat]) grupos[cat] = [];
            grupos[cat].push(p);
        }
    }

    return grupos;
});

const showResumen = ref(false);
const toggleResumen = () => {
    showResumen.value = !showResumen.value;
};
</script>


<template>
    <AppLayout>

        <Head title="Crear Pedido" />

        <div class="w-full px-4 sm:px-6 text-[#4b3621] dark:text-white overflow-x-hidden max-w-full">
            <h1 class="text-2xl font-bold mb-4 text-[#593E25] dark:text-[#d9a679]">Ordenar productos</h1>

            <div class="flex flex-col-reverse lg:flex-row gap-6">
                <!-- Productos -->
                <div class="w-full lg:w-2/3">
                    <input v-model="filtro" type="text" placeholder="Buscar productos..."
                        class="mb-4 w-full border border-[#c5a880] dark:border-[#8c5c3b] rounded px-3 py-2 text-sm bg-white dark:bg-[#1d1b16] text-[#4b3621] dark:text-white" />

                    <div v-for="(productos, categoria) in productosAgrupados" :key="categoria" class="mb-10">
                        <h2 class="text-lg font-semibold mb-2 text-[#593E25] dark:text-[#d9a679]">
                            {{ categoria }}
                        </h2>

                        <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-4">
                            <div v-for="producto in productos" :key="producto.id_producto"
                                class="bg-white dark:bg-[#1d1b16] border border-[#c5a880] dark:border-[#8c5c3b] rounded-lg p-3 shadow-md flex flex-col justify-between">
                                <img :src="producto.imagen" alt="Imagen"
                                    class="h-32 w-full object-cover rounded-md mb-2" />
                                <h3 class="font-semibold text-sm">{{ producto.nombre }}</h3>
                                <p class="text-xs">{{ producto.categorium?.nombre || 'Sin categoría' }}</p>
                                <p class="text-sm font-medium mt-1">{{ producto.precio.toFixed(2) }} Bs</p>
                                <button @click="agregarProducto(producto)"
                                    class="mt-2 bg-[#a47148] hover:bg-[#8c5c3b] text-white text-xs py-1 px-2 rounded">
                                    Agregar
                                </button>
                            </div>
                        </div>
                    </div>

                </div>

                <!-- Carrito -->
                <div class="w-full lg:w-1/3 bg-[#f4eee7] dark:bg-[#2c211b] rounded-xl border border-[#c5a880] dark:border-[#8c5c3b] p-4 shadow-lg max-h-[calc(100vh-150px)] overflow-y-auto lg:sticky lg:top-24"
                    v-if="carrito.length">
                    <h2 class="text-lg font-semibold mb-2">Pedido</h2>

                    <div class="space-y-4">
                        <div v-for="(item, index) in carrito" :key="item.id_producto"
                            class="flex flex-col border-b pb-3">
                            <p class="font-semibold">{{ item.nombre }}</p>
                            <input v-model="item.cantidad" type="number" min="1"
                                class="mt-1 w-20 border px-2 py-1 rounded text-sm bg-white dark:bg-[#1d1b16] border-[#c5a880] dark:border-[#8c5c3b]" />
                            <textarea v-model="item.comentario" placeholder="Comentario opcional"
                                class="mt-2 w-full border px-2 py-1 rounded text-sm bg-white dark:bg-[#1d1b16]" :class="[
                                    'border',
                                    item.comentario.length > 255 ? 'border-red-500 dark:border-red-400' : 'border-[#c5a880] dark:border-[#8c5c3b]'
                                ]" />
                            <p v-if="item.comentario.length > 255" class="text-xs text-red-500 mt-1">
                                El comentario no puede tener más de 255 caracteres.
                            </p>

                            <div class="flex justify-between items-center mt-1">
                                <p class="text-sm font-medium">{{ (item.precio * item.cantidad).toFixed(2) }} Bs</p>
                                <button @click="quitarProducto(item.id_producto)"
                                    class="text-red-600 text-xs hover:underline">
                                    Quitar
                                </button>
                            </div>
                        </div>
                    </div>

                    <div class="flex justify-between mt-4 font-bold">
                        <p>Total:</p>
                        <p>{{ total.toFixed(2) }} Bs</p>
                    </div>

                    <div class="flex justify-end gap-3 mt-4">
                        <div class="flex justify-end mb-2">
                            <button @click="toggleResumen" class="text-sm text-blue-600 hover:underline">
                                🧾 Ver resumen
                            </button>
                        </div>
                        <button @click="cancelarPedido"
                            class="bg-red-500 hover:bg-red-600 text-white px-4 py-2 rounded shadow text-sm">
                            Cancelar
                        </button>
                        <button @click="enviarPedido"
                            class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded shadow text-sm">
                            Enviar pedido
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
    <!-- Modal Resumen -->
    <div v-if="showResumen" class="fixed inset-0 bg-black/40 backdrop-blur-sm z-50 flex items-center justify-center p-4"
        @click.self="toggleResumen">
        <div
            class="bg-white dark:bg-[#1d1b16] border border-[#c5a880] dark:border-[#8c5c3b] rounded-xl shadow-xl w-full max-w-md p-6 text-sm text-[#4b3621] dark:text-white">
            <h2 class="text-lg font-semibold mb-4 text-center">🧾 Resumen del Pedido</h2>

            <ul class="divide-y divide-[#c5a880] dark:divide-[#8c5c3b] max-h-64 overflow-y-auto mb-4">
                <li v-for="item in carrito" :key="item.id_producto" class="py-2 flex flex-col gap-1">
                    <div class="flex justify-between">
                        <div>
                            <p class="font-medium">{{ item.nombre }}</p>
                            <p class="text-xs text-gray-500 dark:text-gray-300">Cantidad: {{ item.cantidad }}</p>
                            <p v-if="item.comentario" class="text-xs italic text-gray-600 dark:text-gray-400 mt-1">
                                “{{ item.comentario }}”
                            </p>
                        </div>
                        <p class="font-semibold self-start">{{ (item.precio * item.cantidad).toFixed(2) }} Bs</p>
                    </div>
                </li>
            </ul>


            <div class="flex justify-between font-bold border-t pt-2">
                <p>Total:</p>
                <p>{{ total.toFixed(2) }} Bs</p>
            </div>

            <div class="mt-6 text-center">
                <button @click="toggleResumen"
                    class="px-4 py-2 rounded border border-[#c5a880] dark:border-[#8c5c3b] hover:bg-[#f0e6d6] dark:hover:bg-[#2a221b]">
                    Cerrar
                </button>
            </div>
        </div>
    </div>

</template>
