<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, router, usePage } from '@inertiajs/vue3';
import { ref, computed, onMounted, watch } from 'vue';
const page = usePage();



// Tipado Producto
interface Producto {
    id_producto: number;
    nombre: string;
    precio: number;
    imagen: string;
    disponibilidad: boolean;
    cantidad_disponible: number;
    categorium?: { nombre: string };
}

// Props
const props = defineProps<{
    productos: Producto[];
    carritoInicial?: {
        id_producto: number;
        nombre: string;
        precio: number;
        cantidad: number;
        comentario: string;
    }[];
    pedidoId?: number;
}>();


// Carrito
const carrito = ref<{ id_producto: number; nombre: string; precio: number; cantidad: number; comentario: string }[]>([]);

// Modal de Error
const showStockModal = ref(false);
const modalMessage = ref('');

// Modal Resumen
const showResumen = ref(false);

//Modal envio correcto
const showSuccessModal = ref(false);
const showErrorModal = ref(false);
const recargarPagina = () => {
    window.location.reload();
};

watch(() => page.props.error, (val: unknown) => {
    if (val) {
        showErrorModal.value = true;
    }
});

// Filtros
const filtro = ref('');
const filtroSoloDisponibles = ref(false);
const filtroSoloAgotados = ref(false);

// Métodos
const abrirModalError = (mensaje: string) => {
    modalMessage.value = mensaje;
    showStockModal.value = true;
};

const cerrarModalError = () => {
    showStockModal.value = false;
    modalMessage.value = '';
};

const toggleResumen = () => {
    showResumen.value = !showResumen.value;
};

const agregarProducto = (producto: Producto) => {
    const existente = carrito.value.find(p => p.id_producto === producto.id_producto);

    if (existente) {
        const stockDisponible = producto.cantidad_disponible;
        if (existente.cantidad < stockDisponible) {
            existente.cantidad++;
        } else {
            abrirModalError(`No hay más stock disponible para ${producto.nombre}.`);
        }
    } else {
        if (producto.cantidad_disponible > 0) {
            carrito.value.push({
                id_producto: producto.id_producto,
                nombre: producto.nombre,
                precio: producto.precio,
                cantidad: 1,
                comentario: ''
            });
        } else {
            abrirModalError(`Producto ${producto.nombre} no tiene stock disponible.`);
        }
    }
};

const quitarProducto = (id: number) => {
    if (carrito.value.length === 1 && props.pedidoId !== undefined) {
        abrirModalError('Para quitar todos los productos del pedido, por favor cancela el pedido.');
        return;
    }

    carrito.value = carrito.value.filter(p => p.id_producto !== id);
};


const enviarPedido = () => {
    if (carrito.value.length === 0) return;

    const payload = {
        items: carrito.value.map(p => ({
            id_producto: p.id_producto,
            cantidad: p.cantidad,
            comentario: p.comentario,
        }))
    };

    const onSuccess = () => {
        carrito.value = [];
        localStorage.removeItem('carrito_pedido');
        showSuccessModal.value = true;
    };

    const onError = (errors: any) => {
        if (errors?.fuera_horario) {
            mensajeHorario.value = errors.fuera_horario;
            showHorarioModal.value = true;
        } else if (errors?.stock) {
            abrirModalError(errors.stock);
        } else {
            showErrorModal.value = true;
        }
    };

    if (props.pedidoId) {
        router.put(`/order/${props.pedidoId}`, payload, {
            onSuccess,
            onError,
            preserveScroll: true
        });
    } else {
        router.post('/order', payload, {
            onSuccess,
            onError,
            preserveScroll: true
        });
    }
};


const cancelarPedido = () => {
    carrito.value = [];
    localStorage.removeItem('carrito_pedido');

    if (props.pedidoId) {
        // Si es edición, volver a la ruta normal de creación
        router.visit('/order');
    }
};


const buscarStock = (id_producto: number) => {
    const producto = props.productos.find(p => p.id_producto === id_producto);
    const original = props.carritoInicial?.find(p => p.id_producto === id_producto);

    if (!producto) return 0;

    // Si estás editando y ya tenías ese producto en el pedido inicial, se suma esa cantidad al stock actual
    const stockDisponible = producto.cantidad_disponible + (original?.cantidad ?? 0);
    return stockDisponible;
};


const validarCantidad = (item: { id_producto: number; cantidad: number | null | undefined }) => {
    if (item.cantidad == null) return;
    const stock = buscarStock(item.id_producto);

    if (item.cantidad > stock) {
        item.cantidad = stock;
        abrirModalError('Cantidad máxima disponible alcanzada.');
    } else if (item.cantidad < 1) {
        item.cantidad = 1;
        abrirModalError('La cantidad mínima es 1.');
    }
};


// Agrupar productos aplicando filtros
const productosAgrupados = computed(() => {
    const grupos: Record<string, Producto[]> = {};
    const termino = filtro.value.toLowerCase();

    for (const p of props.productos) {
        const coincideBusqueda =
            !termino ||
            p.nombre.toLowerCase().includes(termino) ||
            (p.categorium?.nombre ?? '').toLowerCase().includes(termino);

        const coincideDisponible = !filtroSoloDisponibles.value || (p.disponibilidad && p.cantidad_disponible > 0);
        const coincideAgotado = !filtroSoloAgotados.value || (!p.disponibilidad || p.cantidad_disponible <= 0);

        if (coincideBusqueda && coincideDisponible && coincideAgotado) {
            const cat = p.categorium?.nombre ?? 'Sin categoría';
            if (!grupos[cat]) grupos[cat] = [];
            grupos[cat].push(p);
        }
    }

    return grupos;
});

// LocalStorage
onMounted(() => {
    if (props.carritoInicial) {
        carrito.value = props.carritoInicial.map(item => ({
            ...item,
            comentario: item.comentario ?? '',
        }));
    } else {
        const data = localStorage.getItem('carrito_pedido');
        if (data) {
            try {
                const parsed = JSON.parse(data);
                pedidoGuardado.value = parsed.map((item: any) => ({
                    ...item,
                    comentario: item.comentario ?? '',
                }));
                showRecuperarModal.value = true;
            } catch (e) {
                console.error('Error al cargar carrito local:', e);
                localStorage.removeItem('carrito_pedido');
            }
        }
    }

    const flash = page.props.flash as { success?: string };

    if (flash?.success && props.pedidoId) {
        showSuccessModal.value = true;
    }
});

watch(carrito, (nuevo) => {
    localStorage.setItem('carrito_pedido', JSON.stringify(nuevo));
}, { deep: true });

// Total
const total = computed(() =>
    carrito.value.reduce((sum, p) => sum + p.precio * p.cantidad, 0)
);
const showHorarioModal = ref(false);
const mensajeHorario = ref('');
onMounted(() => {
    // ... ya existente

    const errores = page.props.errors as Record<string, string>;

    if (errores?.fuera_horario) {
        mensajeHorario.value = errores.fuera_horario;
        showHorarioModal.value = true;
    }
});
const showRecuperarModal = ref(false);
const pedidoGuardado = ref<any[] | null>(null);
    const aceptarRecuperarPedido = () => {
        if (pedidoGuardado.value) {
            carrito.value = pedidoGuardado.value;
        }
        showRecuperarModal.value = false;
    };

    const rechazarRecuperarPedido = () => {
        localStorage.removeItem('carrito_pedido');
        showRecuperarModal.value = false;
    };

</script>

<template>
    <AppLayout>

        <Head title="Crear Pedido" />

        <div class="w-full px-4 sm:px-6 text-[#4b3621] dark:text-white overflow-x-hidden max-w-full">
            <h1 class="text-2xl font-bold mb-4 text-[#593E25] dark:text-[#d9a679]">Ordenar productos</h1>

            <!-- Filtros -->
            <div class="flex flex-wrap gap-2 items-center mb-4">
                <input v-model="filtro" type="text" placeholder="Buscar productos..."
                    class="flex-1 min-w-[150px] border px-3 py-2 rounded text-sm border-[#c5a880] dark:border-[#8c5c3b] bg-white dark:bg-[#1d1b16] text-[#4b3621] dark:text-white" />

                <label class="flex items-center gap-2 text-sm text-[#4b3621] dark:text-white">
                    <input type="checkbox" v-model="filtroSoloDisponibles"
                        class="form-checkbox h-4 w-4 text-[#a47148] border-[#c5a880] dark:border-[#8c5c3b]" />
                    Solo disponibles
                </label>

                <label class="flex items-center gap-2 text-sm text-[#4b3621] dark:text-white">
                    <input type="checkbox" v-model="filtroSoloAgotados"
                        class="form-checkbox h-4 w-4 text-[#a47148] border-[#c5a880] dark:border-[#8c5c3b]" />
                    Solo agotados
                </label>

                <button @click="() => { filtro = ''; filtroSoloDisponibles = false; filtroSoloAgotados = false; }"
                    class="bg-red-600 hover:bg-red-700 text-white text-xs px-3 py-2 rounded shadow">
                    Limpiar filtros
                </button>
                <button @click="() => router.visit('/my-orders')"
                    class="bg-green-600 hover:bg-green-700 text-white text-xs px-3 py-2 rounded shadow">
                    Mis pedidos
                </button>


            </div>

            <div class="flex flex-col-reverse lg:flex-row gap-6">
                <!-- Productos -->
                <div class="w-full lg:w-2/3">
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
                                <p class="text-xs text-gray-600 dark:text-gray-300 mt-1">
                                    Disponibles: {{ producto.cantidad_disponible }}
                                </p>
                                <button @click="agregarProducto(producto)"
                                    class="mt-2 bg-[#a47148] hover:bg-[#8c5c3b] text-white text-xs py-1 px-2 rounded">
                                    Agregar
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Carrito -->
                <div class="w-full lg:w-1/3 bg-[#f4eee7] dark:bg-[#2c211b] rounded-xl border border-[#c5a880] dark:border-[#8c5c3b] p-4 shadow-lg overflow-y-auto max-h-[60vh]"
                    v-if="carrito.length">


                    <h2 class="text-lg font-semibold mb-2">Pedido</h2>

                    <div class="space-y-4">
                        <div v-for="item in carrito" :key="item.id_producto" class="flex flex-col border-b pb-3">
                            <p class="font-semibold">{{ item.nombre }}</p>
                            <input v-model.number="item.cantidad" type="number" min="1"
                                :max="buscarStock(item.id_producto)" @blur="validarCantidad(item)"
                                class="mt-1 w-20 border px-2 py-1 rounded text-sm bg-white dark:bg-[#1d1b16] border-[#c5a880] dark:border-[#8c5c3b]"
                                onkeypress="if (event.key.match(/[^0-9]/)) event.preventDefault();" />

                            <textarea v-model="item.comentario" placeholder="Comentario opcional"
                                class="mt-2 w-full border px-2 py-1 rounded text-sm bg-white dark:bg-[#1d1b16]"
                                :class="[item.comentario.length > 255 ? 'border-red-500 dark:border-red-400' : 'border-[#c5a880] dark:border-[#8c5c3b]']" />
                            <p v-if="item.comentario && item.comentario.length > 255" class="text-xs text-red-500 mt-1">
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
                        <button @click="toggleResumen" class="text-sm text-blue-600 hover:underline mb-2">
                            🧾 Ver resumen
                        </button>
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

        <!-- Modal de Resumen -->
        <div v-if="showResumen"
            class="fixed inset-0 bg-black/40 backdrop-blur-sm z-50 flex items-center justify-center p-4"
            @click.self="toggleResumen">
            <div class="bg-white dark:bg-[#1d1b16] p-6 rounded-lg w-full max-w-md shadow-xl">
                <h2 class="text-lg font-bold mb-4 text-center">🧾 Resumen del Pedido</h2>
                <ul class="divide-y divide-[#c5a880] dark:divide-[#8c5c3b] max-h-64 overflow-y-auto mb-4">
                    <li v-for="item in carrito" :key="item.id_producto" class="py-2 flex flex-col gap-1">
                        <div class="flex justify-between">
                            <div>
                                <p class="font-medium">{{ item.nombre }}</p>
                                <p class="text-xs text-gray-500 dark:text-gray-300">Cantidad: {{ item.cantidad }}</p>
                                <p v-if="item.comentario" class="text-xs italic mt-1">
                                    "{{ item.comentario }}"
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
                        class="px-4 py-2 border rounded hover:bg-[#f0e6d6] dark:hover:bg-[#2a221b]">
                        Cerrar
                    </button>
                </div>
            </div>
        </div>

        <!-- Modal de Error Stock -->
        <div v-if="showStockModal"
            class="fixed inset-0 bg-black/40 backdrop-blur-sm z-50 flex items-center justify-center p-4"
            @click.self="cerrarModalError">
            <div class="bg-white dark:bg-[#2c211b] p-6 rounded-lg w-full max-w-sm shadow-xl">
                <h2 class="text-lg font-bold mb-4">Error</h2>
                <p class="mb-6 text-sm">{{ modalMessage }}</p>
                <div class="flex justify-center">
                    <button @click="cerrarModalError"
                        class="px-4 py-2 bg-[#a47148] hover:bg-[#8c5c3b] text-white rounded">
                        Cerrar
                    </button>
                </div>
            </div>
        </div>
        <!-- Modal de Éxito -->
        <div v-if="showSuccessModal"
            class="fixed inset-0 bg-black/40 backdrop-blur-sm z-50 flex items-center justify-center p-4">
            <div class="bg-white dark:bg-[#2c211b] p-6 rounded-lg w-full max-w-sm shadow-xl text-center">
                <h2 class="text-lg font-bold text-green-600 dark:text-green-400 mb-4">¡Pedido enviado!</h2>
                <p class="text-sm mb-6">El pedido fue registrado y enviado correctamente a cocina.</p>
                <div class="flex justify-center gap-4">
                    <button @click="showSuccessModal = false"
                        class="px-4 py-2 bg-green-600 hover:bg-green-700 text-white rounded">
                        Aceptar
                    </button>
                    <button @click="router.visit('/my-orders')"
                        class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded">
                        Ver mis pedidos
                    </button>
                </div>
            </div>
        </div>
        <div v-if="showErrorModal" class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center">
            <div class="bg-white dark:bg-[#2c211b] rounded-lg shadow-xl p-6 max-w-md w-full">
                <h2 class="text-lg font-bold text-red-600 mb-2">Error</h2>
                <p class="text-sm mb-4">Ocurrió un error inesperado. Intente nuevamente. La cantidad mínima de un
                    producto es: 1</p>
                <div class="flex justify-end">
                    <button @click="recargarPagina" class="bg-red-600 text-white px-4 py-2 rounded hover:bg-red-700">
                        Recargar
                    </button>
                </div>
            </div>
        </div>
        <!-- Modal de Horario Fuera de Rango -->
        <div v-if="showHorarioModal"
            class="fixed inset-0 bg-black/40 backdrop-blur-sm z-50 flex items-center justify-center p-4">
            <div class="bg-white dark:bg-[#2c211b] p-6 rounded-lg w-full max-w-sm shadow-xl text-center">
                <h2 class="text-lg font-bold text-yellow-600 dark:text-yellow-400 mb-4">⏰ Fuera del horario</h2>
                <p class="text-sm mb-6">{{ mensajeHorario }}</p>
                <div class="flex justify-center">
                    <button @click="showHorarioModal = false"
                        class="px-4 py-2 bg-yellow-600 hover:bg-yellow-700 text-white rounded">
                        Aceptar
                    </button>
                </div>
            </div>
        </div>
        <!-- Modal de recuperación de pedido -->
        <div v-if="showRecuperarModal"
            class="fixed inset-0 bg-black/40 backdrop-blur-sm z-50 flex items-center justify-center p-4">
            <div class="bg-white dark:bg-[#2c211b] p-6 rounded-lg w-full max-w-sm shadow-xl text-center">
                <h2 class="text-lg font-bold mb-4 text-[#4b3621] dark:text-[#d9a679]">¿Recuperar pedido anterior?</h2>
                <p class="text-sm mb-6">Se encontró un pedido anterior sin enviar. ¿Deseas restaurarlo?</p>
                <div class="flex justify-center gap-4">
                    <button @click="rechazarRecuperarPedido"
                        class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white rounded">
                        No, descartarlo
                    </button>
                    <button @click="aceptarRecuperarPedido"
                        class="px-4 py-2 bg-green-600 hover:bg-green-700 text-white rounded">
                        Sí, restaurar
                    </button>
                </div>
            </div>
        </div>

    </AppLayout>
</template>
