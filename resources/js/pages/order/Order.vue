<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import { ref, computed } from 'vue';

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

// Agregar producto al carrito
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

// Eliminar del carrito
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

  router.post('/ordenar', {
    items: carrito.value.map(p => ({
      id_producto: p.id_producto,
      cantidad: p.cantidad,
      comentario: p.comentario,
    }))
  }, {
    onSuccess: () => carrito.value = []
  });
};
const filtro = ref('');

const productosFiltrados = computed(() => {
  if (!filtro.value) return props.productos;
  const termino = filtro.value.toLowerCase();
  return props.productos.filter(p =>
    p.nombre.toLowerCase().includes(termino) ||
    (p.categorium?.nombre ?? '').toLowerCase().includes(termino)
  );
});

</script>
<template>
    <AppLayout>
      <Head title="Crear Pedido" />
  
      <div class="p-4 sm:p-6 text-[#4b3621] dark:text-white">
        <h1 class="text-2xl font-bold mb-4 text-[#593E25] dark:text-[#d9a679]">Ordenar productos</h1>
  
        <div class="flex flex-col-reverse lg:flex-row gap-6">

          <!-- Productos disponibles -->
          <div class="w-full lg:w-2/3">
            <!-- Filtro de búsqueda -->
            <input
              v-model="filtro"
              type="text"
              placeholder="Buscar productos..."
              class="mb-4 w-full border border-[#c5a880] dark:border-[#8c5c3b] rounded px-3 py-2 text-sm bg-white dark:bg-[#1d1b16] text-[#4b3621] dark:text-white"
            />
  
            <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-4">
              <div
                v-for="producto in productosFiltrados"
                :key="producto.id_producto"
                class="bg-white dark:bg-[#1d1b16] border border-[#c5a880] dark:border-[#8c5c3b] rounded-lg p-3 shadow-md flex flex-col justify-between"
              >
                <img :src="producto.imagen" alt="Imagen" class="h-32 w-full object-cover rounded-md mb-2" />
                <h3 class="font-semibold text-sm">{{ producto.nombre }}</h3>
                <p class="text-xs">{{ producto.categorium?.nombre || 'Sin categoría' }}</p>
                <p class="text-sm font-medium mt-1">{{ producto.precio.toFixed(2) }} Bs</p>
                <button
                  @click="agregarProducto(producto)"
                  class="mt-2 bg-[#a47148] hover:bg-[#8c5c3b] text-white text-xs py-1 px-2 rounded"
                >
                  Agregar
                </button>
              </div>
            </div>
          </div>
  
          <!-- Carrito -->
          <div
            class="w-full lg:w-1/3 bg-[#f4eee7] dark:bg-[#2c211b] rounded-xl border border-[#c5a880] dark:border-[#8c5c3b] p-4 shadow-lg max-h-[calc(100vh-150px)] overflow-y-auto sticky top-24"
            v-if="carrito.length"
          >
            <h2 class="text-lg font-semibold mb-2">Carrito</h2>
  
            <div class="space-y-4">
              <div
                v-for="(item, index) in carrito"
                :key="item.id_producto"
                class="flex flex-col border-b pb-3"
              >
                <p class="font-semibold">{{ item.nombre }}</p>
                <input
                  v-model="item.cantidad"
                  type="number"
                  min="1"
                  class="mt-1 w-20 border px-2 py-1 rounded text-sm bg-white dark:bg-[#1d1b16] border-[#c5a880] dark:border-[#8c5c3b]"
                />
                <textarea
                  v-model="item.comentario"
                  placeholder="Comentario opcional"
                  class="mt-2 w-full border px-2 py-1 rounded text-sm bg-white dark:bg-[#1d1b16] border-[#c5a880] dark:border-[#8c5c3b]"
                ></textarea>
                <div class="flex justify-between items-center mt-1">
                  <p class="text-sm font-medium">{{ (item.precio * item.cantidad).toFixed(2) }} Bs</p>
                  <button
                    @click="quitarProducto(item.id_producto)"
                    class="text-red-600 text-xs hover:underline"
                  >
                    Quitar
                  </button>
                </div>
              </div>
            </div>
  
            <div class="flex justify-between mt-4 font-bold">
              <p>Total:</p>
              <p>{{ total.toFixed(2) }} Bs</p>
            </div>
  
            <div class="flex justify-end mt-4">
              <button
                @click="enviarPedido"
                class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded shadow text-sm"
              >
                Enviar pedido
              </button>
            </div>
          </div>
        </div>
      </div>
    </AppLayout>
  </template>
  
