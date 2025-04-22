<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import type { PageProps } from '@/types';
import { computed, ref } from 'vue';

const props = defineProps<{ productos: any[] }>();
const page = usePage<PageProps>();

const showModal = ref(false);
const selectedProductId = ref<number | null>(null);
const selectedProductName = ref('');

const confirmEliminar = (id: number, nombre: string) => {
    selectedProductId.value = id;
    selectedProductName.value = nombre;
    showModal.value = true;
};

const cancelarEliminar = () => {
    showModal.value = false;
    selectedProductId.value = null;
    selectedProductName.value = '';
};

const eliminarProducto = () => {
    if (selectedProductId.value !== null) {
        router.delete(`/productos/${selectedProductId.value}`, {
            onSuccess: () => cancelarEliminar(),
        });
    }
};

const productosPorCategoria = computed(() => {
    const grupos: Record<string, any[]> = {};
    for (const producto of productosFiltrados.value) {
        const categoria = producto.categorium?.nombre ?? 'Sin categoría';
        if (!grupos[categoria]) grupos[categoria] = [];
        grupos[categoria].push(producto);
    }
    return grupos;
});

const flippedCards = ref<number[]>([]);

const toggleFlip = (id: number) => {
    if (flippedCards.value.includes(id)) {
        flippedCards.value = flippedCards.value.filter(pid => pid !== id);
    } else {
        flippedCards.value.push(id);
    }
};
const toggleDisponibilidad = (id: number) => {
    router.put(`/productos/${id}/toggle-disponibilidad`, {}, {
        preserveScroll: true,
        onError: (err) => console.error(err),
    });
};
const filtroNombre = ref('');
const filtroCategoria = ref('');
const filtroPrecioMax = ref('');
const filtroDisponible = ref(false);

const productosFiltrados = computed(() => {
    return props.productos.filter(p => {
        const coincideNombre = filtroNombre.value === '' || p.nombre.toLowerCase().includes(filtroNombre.value.toLowerCase());
        const coincideCategoria = filtroCategoria.value === '' || p.id_categoria == filtroCategoria.value;
        const coincidePrecio = filtroPrecioMax.value === '' || parseFloat(p.precio) <= parseFloat(filtroPrecioMax.value);
        const coincideDisponibilidad = !filtroDisponible.value || p.disponibilidad;
        return coincideNombre && coincideCategoria && coincidePrecio && coincideDisponibilidad;
    });
});


</script>

<template>
    <AppLayout>
        <Head title="Productos" />

        <div class="p-3 sm:p-6 text-[#4b3621] dark:text-white w-full max-w-full overflow-x-hidden">
            <!-- Título -->
            <h1 class="text-lg sm:text-2xl font-bold text-[#593E25] dark:text-[#d9a679] mb-3">Productos</h1>

            <!-- Filtros -->
            <div class="w-full flex flex-wrap gap-2 sm:gap-4 mb-4">
                <input v-model="filtroNombre" type="text" placeholder="Nombre"
                    class="flex-1 min-w-[150px] border px-3 py-2 rounded text-sm border-[#c5a880] dark:border-[#8c5c3b] bg-white dark:bg-[#1d1b16] text-[#4b3621] dark:text-white" />

                <select v-model="filtroCategoria"
                    class="flex-1 min-w-[150px] border px-3 py-2 rounded text-sm border-[#c5a880] dark:border-[#8c5c3b] bg-white dark:bg-[#1d1b16] text-[#4b3621] dark:text-white">
                    <option value="">Categoría</option>
                    <option
                        v-for="cat in [...new Set(props.productos.map(p => ({ id: p.id_categoria, nombre: p.categorium?.nombre })))]"
                        :key="cat.id" :value="cat.id">
                        {{ cat.nombre }}
                    </option>
                </select>

                <input v-model="filtroPrecioMax" type="number" min="0" step="0.01" placeholder="Máx. precio"
                    class="flex-1 min-w-[120px] border px-3 py-2 rounded text-sm border-[#c5a880] dark:border-[#8c5c3b] bg-white dark:bg-[#1d1b16] text-[#4b3621] dark:text-white" />

                <label class="flex items-center gap-2 text-sm text-[#4b3621] dark:text-white">
                    <input type="checkbox" v-model="filtroDisponible"
                        class="form-checkbox h-4 w-4 text-[#a47148] border-[#c5a880] dark:border-[#8c5c3b] bg-white dark:bg-[#1d1b16]" />
                    Solo disponibles
                </label>

                <button
                    @click="() => { filtroNombre = ''; filtroCategoria = ''; filtroPrecioMax = ''; filtroDisponible = false; }"
                    class="bg-red-600 hover:bg-red-700 text-white text-sm rounded px-4 py-2">
                    Limpiar filtros
                </button>
            </div>

            <!-- Botones acción -->
            <div class="w-full flex flex-wrap justify-start sm:justify-end gap-2 mb-6">
                <Link href="/productos/deleted"
                    class="bg-[#6b4f3c] hover:bg-[#8c5c3b] text-white text-xs px-3 py-2 rounded shadow">
                    Ver eliminados
                </Link>
                <Link href="/productos/create"
                    class="bg-[#a47148] hover:bg-[#8c5c3b] text-white text-xs px-3 py-2 rounded shadow">
                    Crear Producto
                </Link>
            </div>


            <!-- Agrupados por categoría -->
            <div v-for="(productos, categoria) in productosPorCategoria" :key="categoria" class="mb-10">
                <h2 class="text-lg font-semibold mb-4 text-[#593E25] dark:text-[#d9a679]">{{ categoria }}</h2>
                <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-6">
                    <div v-for="producto in productos" :key="producto.id_producto" class="relative perspective"
                        @click="toggleFlip(producto.id_producto)">
                        <div
                            :class="['transition-transform duration-500 transform-style-preserve-3d', 'w-full h-64 rounded-xl shadow-lg cursor-pointer relative', flippedCards.includes(producto.id_producto) ? 'rotate-y-180' : '']">

                            <!-- Frente -->
                            <div
                                class="absolute w-full h-full backface-hidden bg-white dark:bg-[#1d1b16] border border-[#c5a880] dark:border-[#8c5c3b] rounded-xl overflow-hidden p-3 flex flex-col justify-between">
                                <div>
                                    <img :src="producto.imagen" alt="imagen"
                                        class="w-full h-32 object-cover rounded-md mb-2">
                                    <h3 class="text-sm font-bold truncate">{{ producto.nombre }}</h3>
                                    <p class="text-xs text-gray-600 dark:text-gray-300">{{ producto.precio.toFixed(2) }}
                                        Bs</p>
                                </div>
                                <div class="flex flex-col items-center text-center mt-2">
                                    <p class="text-xs mt-1 font-medium text-[#4b3621] dark:text-white">
                                        {{ producto.disponibilidad ? 'Disponible' : 'Agotado' }}
                                    </p>
                                    <button @click.stop="toggleDisponibilidad(producto.id_producto)"
                                        class="mt-1 bg-yellow-500 hover:bg-yellow-600 text-white text-[11px] font-medium py-1 px-3 rounded shadow">
                                        {{ producto.disponibilidad ? 'Marcar como Agotado' : 'Marcar como Disponible' }}
                                    </button>
                                </div>
                            </div>


                            <!-- Reverso -->
                            <div
                                class="absolute w-full h-full backface-hidden rotate-y-180 bg-[#faf4ed] dark:bg-[#2c211b] border border-[#c5a880] dark:border-[#8c5c3b] rounded-xl p-3 flex flex-col justify-between">
                                <div>
                                    <h3 class="text-sm font-semibold mb-2">{{ producto.nombre }}</h3>
                                    <p class="text-xs whitespace-pre-line break-words">
                                        {{ producto.descripcion || 'Sin descripción' }}
                                    </p>

                                </div>
                                <div class="mt-3 flex justify-between gap-2">
                                    <Link :href="`/productos/${producto.id_producto}/edit`"
                                        class="bg-blue-600 hover:bg-blue-700 text-white text-xs font-semibold py-1 px-2 rounded w-full text-center">
                                    Editar
                                    </Link>
                                    <button @click.stop="confirmEliminar(producto.id_producto, producto.nombre)"
                                        class="bg-red-600 hover:bg-red-700 text-white text-xs font-semibold py-1 px-2 rounded w-full">
                                        Eliminar
                                    </button>

                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal eliminación -->
        <div v-if="showModal" class="fixed inset-0 bg-black/40 flex items-center justify-center z-50 p-2 sm:p-4">
            <div class="bg-white dark:bg-[#2c211b] p-4 sm:p-6 rounded-lg w-full max-w-sm shadow-xl text-sm">
                <h2 class="text-lg font-bold mb-3 text-[#593E25] dark:text-[#d9a679]">Confirmar eliminación</h2>
                <p class="mb-4">¿Estás seguro que deseas eliminar el producto <strong>{{ selectedProductName
                }}</strong>?</p>
                <div class="flex justify-end gap-2">
                    <button @click="cancelarEliminar" class="px-3 py-1.5 border rounded">Cancelar</button>
                    <button @click="eliminarProducto"
                        class="bg-red-600 text-white px-3 py-1.5 rounded hover:bg-red-700">
                        Eliminar
                    </button>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<style scoped>
.perspective {
    perspective: 1000px;
}

.backface-hidden {
    backface-visibility: hidden;
    transform-style: preserve-3d;
}

.rotate-y-180 {
    transform: rotateY(180deg);
}

.transform-style-preserve-3d {
    transform-style: preserve-3d;
}
</style>
