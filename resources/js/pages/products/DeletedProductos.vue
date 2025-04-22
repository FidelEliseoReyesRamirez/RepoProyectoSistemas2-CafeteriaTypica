<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps<{ productos: any[] }>();
const showRestoreModal = ref(false);
const selectedProductId = ref<number | null>(null);
const selectedProductName = ref('');

const confirmarRestaurar = (id: number, nombre: string) => {
    selectedProductId.value = id;
    selectedProductName.value = nombre;
    showRestoreModal.value = true;
};

const cancelarRestaurar = () => {
    showRestoreModal.value = false;
    selectedProductId.value = null;
    selectedProductName.value = '';
};

const restaurarProducto = () => {
    if (selectedProductId.value !== null) {
        router.post(
            `/productos/${selectedProductId.value}/restore`,
            { _method: 'put' },
            {
                onSuccess: () => {
                    cancelarRestaurar();
                    router.visit('/productos/deleted', { preserveScroll: true });
                },
                onError: (errors) => {
                    console.error('Error al restaurar producto:', errors);
                },
            }
        );
    }
};
</script>

<template>
    <AppLayout>

        <Head title="Productos Eliminados" />

        <div class="p-3 sm:p-6 text-[#4b3621] dark:text-white">
            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-2 sm:gap-4 mb-4">
                <h1 class="text-lg sm:text-2xl font-bold text-[#593E25] dark:text-[#d9a679]">Productos Eliminados</h1>
                <Link href="/productos"
                    class="bg-[#6b4f3c] hover:bg-[#8c5c3b] text-white text-xs sm:text-sm px-3 py-1.5 rounded shadow">
                Volver al listado
                </Link>
            </div>

            <!-- Tabla Escritorio -->
            <div class="overflow-x-auto w-full">
                <div
                    class="min-w-full border border-[#c5a880] dark:border-[#8c5c3b] rounded-xl bg-white dark:bg-[#1d1b16]">
                    <table class="w-full table-auto text-xs sm:text-sm hidden sm:table">
                        <thead class="bg-neutral-100 dark:bg-neutral-800">
                            <tr>
                                <th class="px-2 py-2 text-left">#</th>
                                <th class="px-2 py-2 text-left">Imagen</th>
                                <th class="px-2 py-2 text-left">Nombre</th>
                                <th class="px-2 py-2 text-left">Precio</th>
                                <th class="px-2 py-2 text-left">Categoría</th>
                                <th class="px-2 py-2 text-left">Acciones</th>
                            </tr>
                        </thead>

                        <tbody>
                            <tr v-for="(producto, i) in productos" :key="producto.id_producto"
                                class="border-t dark:border-neutral-700">
                                <td class="px-2 py-2">{{ i + 1 }}</td>
                                <td class="px-2 py-2">
                                    <img :src="producto.imagen" alt="img" class="w-10 h-10 object-cover rounded" />
                                </td>
                                <td class="px-2 py-2">{{ producto.nombre }}</td>
                                <td class="px-2 py-2">{{ producto.precio.toFixed(2) }} Bs</td>
                                <td class="px-2 py-2">{{ producto.categorium?.nombre ?? 'Sin categoría' }}</td>
                                <td class="px-2 py-2">
                                    <button @click="confirmarRestaurar(producto.id_producto, producto.nombre)"
                                        class="bg-green-600 hover:bg-green-700 text-white text-xs py-1 px-2 rounded">
                                        Restaurar
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                    </table>

                    <!-- Vista Móvil -->
                    <div class="sm:hidden divide-y divide-[#d5c2a1] dark:divide-[#6c4f3c]">
                        <div v-for="(producto, i) in productos" :key="producto.id_producto" class="px-3 py-3">
                            <p class="text-xs"><span class="font-semibold">#:</span> {{ i + 1 }}</p>
                            <p class="text-xs">
                                <span class="font-semibold">Imagen:</span><br />
                                <img :src="producto.imagen" alt="img" class="w-16 h-16 object-cover rounded mt-1" />
                            </p>
                            <p class="text-xs"><span class="font-semibold">Nombre:</span> {{ producto.nombre }}</p>
                            <p class="text-xs"><span class="font-semibold">Precio:</span> {{ producto.precio }} Bs</p>
                            <p class="text-xs"><span class="font-semibold">Categoría:</span> {{
                                producto.categorium?.nombre ?? 'Sin categoría' }}</p>
                            <div class="mt-2">
                                <button @click="confirmarRestaurar(producto.id_producto, producto.nombre)"
                                    class="bg-green-600 hover:bg-green-700 text-white text-xs font-semibold py-1 px-2 rounded">
                                    Restaurar
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal -->
        <div v-if="showRestoreModal" class="fixed inset-0 bg-black/40 flex items-center justify-center z-50 p-2 sm:p-4">
            <div class="bg-white dark:bg-[#2c211b] p-4 sm:p-6 rounded-lg w-full max-w-sm shadow-xl text-xs sm:text-sm">
                <h2 class="text-base sm:text-lg font-bold mb-3 text-[#593E25] dark:text-[#d9a679]">Confirmar
                    restauración</h2>
                <p class="mb-4">¿Deseas restaurar el producto <strong>{{ selectedProductName }}</strong>?</p>
                <div class="flex flex-col sm:flex-row justify-end gap-2 sm:gap-4">
                    <button @click="cancelarRestaurar" class="px-3 py-1.5 border rounded">Cancelar</button>
                    <button @click="restaurarProducto"
                        class="bg-green-600 text-white px-3 py-1.5 rounded hover:bg-green-700">
                        Restaurar
                    </button>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
