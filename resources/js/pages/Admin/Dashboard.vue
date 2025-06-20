<script setup lang="ts">
import { ref, computed, watch, onMounted } from 'vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { usePage } from '@inertiajs/vue3';
import axios from 'axios';
import { RefreshCw, Brain, Zap, Download, AlertTriangle, TrendingUp, Calendar, Award, Info } from 'lucide-vue-next';

// Interfaces
interface ForecastItem { ds: string; yhat: number; real?: number; }
interface VentaDia { dia: string; ventas: number; }
interface ProductoTop { nombre: string; total_vendido: number; }
interface ProductoTendencia { nombre: string; crecimiento: number; ventas_actuales: number; ventas_anteriores: number; }
interface ProductoEstacional { nombre: string; temporada: string; alerta: string; }
interface AlertaStock { nombre: string; stock_actual: number; stock_minimo: number; dias_restantes: number; }
interface ProductoSegmentado { id: number; nombre: string; categoria: string; prediccion: number; }
interface Metrics { ventasSemana: number; }

interface PageProps {
    forecast: ForecastItem[];
    porProducto: Record<number, ProductoSegmentado[]>;
    productoTendencia: ProductoTendencia;
    metrics: Metrics;
    topProductos: ProductoTop[];
    ventasDiarias: VentaDia[];
    productosEstacionales: ProductoEstacional[];
    alertasStock: AlertaStock[];
    debug?: {
        forecastExists: boolean;
        totalPedidos: number;
        totalDetalles: number;
        forecastGeneralCount: number;
        forecastProductCount: number;
    };
}

// Props del componente
const {
    forecast = [],
    porProducto = {},
    productoTendencia = { nombre: 'N/A', crecimiento: 0, ventas_actuales: 0, ventas_anteriores: 0 },
    metrics = { ventasSemana: 0, clientesAtendidos: 0 },
    topProductos = [],
    ventasDiarias = [],
    productosEstacionales = [],
    alertasStock = []
} = usePage().props as unknown as PageProps;

// Estado reactivo
const isGenerating = ref(false);
const isExporting = ref(false);
const lastUpdated = ref(new Date());
const error = ref('');
const success = ref('');
const showTooltip = ref<string | null>(null);
const selectedCategory = ref('A');

// Computed properties optimizados
const hasData = computed(() => forecast.length > 0 || Object.keys(porProducto).length > 0);

const productosFiltered = computed(() => {
    if (!porProducto || Object.keys(porProducto).length === 0) return {};

    return Object.entries(porProducto).reduce((acc, [key, items]) => {
        const filtered = items.filter(item => item.categoria === selectedCategory.value);
        if (filtered.length > 0) {
            acc[key] = filtered;
        }
        return acc;
    }, {} as Record<string, ProductoSegmentado[]>);
});

const mesAnterior = computed(() => {
    const fecha = new Date();
    fecha.setMonth(fecha.getMonth() - 1);
    return fecha.toLocaleDateString('es-ES', { month: 'long', year: 'numeric' });
});

// Período fijo de 6 meses (3 meses antes y 3 meses después de hoy)
const filteredForecast = computed(() => {
    if (!forecast.length) return [];

    const today = new Date();
    const threeMonthsBefore = new Date(today);
    threeMonthsBefore.setMonth(today.getMonth() - 3);

    const threeMonthsAfter = new Date(today);
    threeMonthsAfter.setMonth(today.getMonth() + 3);

    return forecast.filter(item => {
        const date = new Date(item.ds);
        return date >= threeMonthsBefore && date <= threeMonthsAfter;
    });
});

// Configuración de gráficos optimizada
const chartConfig = computed(() => ({
    general: {
        type: 'line',
        data: {
            labels: filteredForecast.value.map(f => new Date(f.ds).toLocaleDateString('es-ES', { month: 'short', day: 'numeric' })),
            datasets: [
                {
                    label: 'Predicción',
                    data: filteredForecast.value.map(f => Math.round(f.yhat)),
                    borderColor: '#3B82F6',
                    backgroundColor: 'rgba(59, 130, 246, 0.1)',
                    tension: 0.4,
                    fill: true,
                    pointRadius: 2,
                    pointHoverRadius: 4
                },
                {
                    label: 'Real',
                    data: filteredForecast.value.map(f => f.real || null),
                    borderColor: '#10B981',
                    backgroundColor: 'rgba(16, 185, 129, 0.1)',
                    tension: 0.4,
                    fill: true,
                    pointRadius: 2,
                    pointHoverRadius: 4
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            interaction: { intersect: false, mode: 'index' },
            plugins: {
                legend: { position: 'top' },
                tooltip: {
                    backgroundColor: 'rgba(0, 0, 0, 0.8)',
                    titleColor: '#fff',
                    bodyColor: '#fff'
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    title: { display: true, text: 'Cantidad' },
                    grid: { color: 'rgba(0, 0, 0, 0.1)' }
                },
                x: {
                    grid: { color: 'rgba(0, 0, 0, 0.1)' }
                }
            }
        }
    }
}));

// Funciones principales
const generarPrediccion = async () => {
    if (isGenerating.value) return;

    isGenerating.value = true;
    error.value = '';
    success.value = '';

    try {
        console.log('Ejecutando script de Prophet...');
        const response = await axios.post('/admin/generar-prediccion', {}, {
            timeout: 900000
        });

        console.log('Respuesta del servidor:', response.data);
        success.value = 'Predicción generada correctamente con Prophet';
        lastUpdated.value = new Date();

        // Recargar después de un breve delay
        setTimeout(() => {
            window.location.reload();
        }, 1500);

    } catch (e: any) {
        console.error('Error al generar predicción:', e);

        if (e.response?.data?.error) {
            error.value = e.response.data.error;
        } else if (e.code === 'ECONNABORTED') {
            error.value = 'La operación tardó demasiado tiempo. Inténtelo de nuevo.';
        } else {
            error.value = 'Error de conexión con el servidor';
        }
    } finally {
        isGenerating.value = false;
    }
};

const exportarCSV = async () => {
    if (isExporting.value || !hasData.value) return;

    isExporting.value = true;
    error.value = '';

    try {
        const response = await fetch('/admin/export-csv', {
            method: 'GET',
            headers: { 'Accept': 'text/csv' }
        });

        if (!response.ok) {
            const errorData = await response.json().catch(() => ({ error: 'Error desconocido' }));
            throw new Error(errorData.error || `Error ${response.status}: ${response.statusText}`);
        }

        const blob = await response.blob();
        const url = window.URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = `forecast_${new Date().toISOString().split('T')[0]}.csv`;
        document.body.appendChild(a);
        a.click();

        // Cleanup
        window.URL.revokeObjectURL(url);
        document.body.removeChild(a);

        success.value = 'CSV exportado correctamente';
        setTimeout(() => success.value = '', 3000);

    } catch (e: any) {
        console.error('Error al exportar CSV:', e);
        error.value = e.message || 'Error al exportar CSV';
        setTimeout(() => error.value = '', 5000);
    } finally {
        isExporting.value = false;
    }
};

const showMetricTooltip = (metric: string) => {
    showTooltip.value = metric;
    setTimeout(() => showTooltip.value = null, 3000);
};

const getTooltipContent = (metric: string) => {
    const tooltips = {
        ventas: forecast.length > 0 ? 'Predicción vs Real: Datos disponibles' : 'Sin datos de predicción',
        alertas: `${alertasStock.length} productos requieren atención`,
        tendencia: productoTendencia.crecimiento > 0 ? `Crecimiento: +${productoTendencia.crecimiento}%` : 'Sin tendencia positiva'
    };
    return tooltips[metric as keyof typeof tooltips] || 'Sin información disponible';
};

// Limpiar mensajes automáticamente
watch([success, error], () => {
    if (success.value) {
        setTimeout(() => success.value = '', 5000);
    }
    if (error.value) {
        setTimeout(() => error.value = '', 8000);
    }
});

// Función helper para formatear números
const formatNumber = (num: number) => {
    return new Intl.NumberFormat('es-ES').format(Math.round(num));
};

// Función helper para obtener clase de categoría
const getCategoryClass = (categoria: string) => {
    const classes = {
        'A': 'bg-green-100 text-green-800 border-green-200',
        'B': 'bg-yellow-100 text-yellow-800 border-yellow-200',
        'C': 'bg-gray-100 text-gray-800 border-gray-200'
    };
    return classes[categoria as keyof typeof classes] || classes.C;
};
// Función helper para obtener clase de posición
const getPositionClass = (index: number) => {
    const classes = [
        'bg-yellow-500 text-white', // 1er lugar
        'bg-gray-400 text-white',   // 2do lugar
        'bg-amber-600 text-white',  // 3er lugar
        'bg-blue-100 text-blue-800' // Resto
    ];
    return classes[index] || classes[3];
};
</script>

<template>
    <AppLayout>
        <div class="p-6 space-y-6 max-w-7xl mx-auto">
            <!-- Header -->
            <div class="flex flex-col lg:flex-row lg:justify-between lg:items-center gap-4">
                <div>
                    <h1 class="text-3xl font-bold flex items-center gap-3">
                        <Brain class="w-8 h-8 text-blue-600" />
                        Dashboard de Predicciones
                    </h1>
                    <p class="text-gray-600 mt-1">
                        Actualizado: {{ lastUpdated.toLocaleString('es-ES') }}
                    </p>
                    <div v-if="!hasData" class="flex items-center gap-2 mt-2 text-amber-600">
                        <AlertTriangle class="w-4 h-4" />
                        <span class="text-sm">No hay datos de predicción. Genere una nueva predicción.</span>
                    </div>
                </div>

                <div class="flex flex-col sm:flex-row gap-3">
                    <button :disabled="isGenerating" @click="generarPrediccion"
                        class="flex items-center justify-center gap-2 px-6 py-3 rounded-lg text-white bg-blue-600 hover:bg-blue-700 disabled:bg-gray-400 disabled:cursor-not-allowed transition-all duration-200 hover:shadow-lg transform hover:scale-105">
                        <component :is="isGenerating ? RefreshCw : Brain" class="w-5 h-5"
                            :class="{ 'animate-spin': isGenerating }" />
                        <span class="font-medium">
                            {{ isGenerating ? 'Generando...' : 'Generar Predicción' }}
                        </span>
                        <Zap v-if="!isGenerating" class="w-4 h-4" />
                    </button>

                    <button @click="exportarCSV" :disabled="!hasData || isExporting"
                        class="flex items-center justify-center gap-2 px-6 py-3 bg-green-600 text-white rounded-lg hover:bg-green-700 disabled:bg-gray-400 disabled:cursor-not-allowed transition-all duration-200 hover:shadow-lg transform hover:scale-105">
                        <component :is="isExporting ? RefreshCw : Download" class="w-4 h-4"
                            :class="{ 'animate-spin': isExporting }" />
                        <span class="font-medium">
                            {{ isExporting ? 'Exportando...' : 'Exportar CSV' }}
                        </span>
                    </button>
                </div>
            </div>

            <!-- Mensajes -->
            <Transition name="slide-down">
                <div v-if="success"
                    class="p-4 bg-green-50 border border-green-200 text-green-800 rounded-xl flex items-center gap-2">
                    <Info class="w-5 h-5 text-green-600" />
                    {{ success }}
                </div>
            </Transition>

            <Transition name="slide-down">
                <div v-if="error"
                    class="p-4 bg-red-50 border border-red-200 text-red-800 rounded-xl flex items-center gap-2">
                    <AlertTriangle class="w-5 h-5 text-red-600" />
                    {{ error }}
                </div>
            </Transition>

            <!-- Métricas Principales -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <!-- Ventas Semana -->
                <div class="p-6 bg-gradient-to-br from-blue-50 to-blue-100 border border-blue-200 rounded-xl hover:shadow-lg transition-all duration-300 cursor-pointer group relative overflow-hidden"
                    @click="showMetricTooltip('ventas')">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-blue-600 mb-1">Ventas Esta Semana</p>
                            <p class="text-2xl font-bold text-blue-800">${{ formatNumber(metrics.ventasSemana) }}</p>
                        </div>
                        <TrendingUp
                            class="w-10 h-10 text-blue-500 group-hover:scale-110 transition-transform duration-200" />
                    </div>
                    <Transition name="fade">
                        <div v-if="showTooltip === 'ventas'"
                            class="absolute inset-x-0 top-0 bg-blue-800 text-white p-3 text-sm rounded-t-xl">
                            {{ getTooltipContent('ventas') }}
                        </div>
                    </Transition>
                </div>

                <!-- Alertas de Stock -->
                <div class="p-6 bg-gradient-to-br from-red-50 to-red-100 border border-red-200 rounded-xl hover:shadow-lg transition-all duration-300 cursor-pointer group relative overflow-hidden"
                    @click="showMetricTooltip('alertas')">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-red-600 mb-1">Alertas de Stock</p>
                            <p class="text-2xl font-bold text-red-800">{{ alertasStock.length }}</p>
                        </div>
                        <AlertTriangle
                            class="w-10 h-10 text-red-500 group-hover:scale-110 transition-transform duration-200" />
                    </div>
                    <Transition name="fade">
                        <div v-if="showTooltip === 'alertas'"
                            class="absolute inset-x-0 top-0 bg-red-800 text-white p-3 text-sm rounded-t-xl">
                            {{ getTooltipContent('alertas') }}
                        </div>
                    </Transition>
                </div>

                <!-- Producto en Tendencia -->
                <div class="p-6 bg-gradient-to-br from-purple-50 to-purple-100 border border-purple-200 rounded-xl hover:shadow-lg transition-all duration-300 cursor-pointer group relative overflow-hidden"
                    @click="showMetricTooltip('tendencia')">
                    <div class="flex items-center justify-between">
                        <div class="min-w-0 flex-1">
                            <p class="text-sm font-medium text-purple-600 mb-1">Tendencia</p>
                            <p class="text-lg font-bold text-purple-800 truncate">{{ productoTendencia.nombre }}</p>
                            <p class="text-sm text-purple-600">+{{ productoTendencia.crecimiento }}%</p>
                        </div>
                        <TrendingUp
                            class="w-10 h-10 text-purple-500 group-hover:scale-110 transition-transform duration-200 flex-shrink-0" />
                    </div>
                    <Transition name="fade">
                        <div v-if="showTooltip === 'tendencia'"
                            class="absolute inset-x-0 top-0 bg-purple-800 text-white p-3 text-sm rounded-t-xl">
                            {{ getTooltipContent('tendencia') }}
                        </div>
                    </Transition>
                </div>
            </div>

            <!-- Predicción General -->
            <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
                <div class="p-6 border-b border-gray-100">
                    <h3 class="text-xl font-semibold flex items-center gap-2">
                        <TrendingUp class="w-6 h-6 text-blue-600" />
                        Predicción General
                    </h3>
                </div>

                <div class="p-6">
                    <div v-if="filteredForecast.length > 0" class="relative">
                        <div class="h-80">
                            <img :src="`https://quickchart.io/chart?c=${encodeURIComponent(JSON.stringify(chartConfig.general))}`"
                                alt="Predicción General" class="w-full h-full object-contain rounded-lg"
                                loading="lazy" />
                        </div>
                        <p class="text-xs text-gray-500 mt-3 flex items-center gap-1">
                            <Info class="w-3 h-3" />
                            Las líneas verdes representan datos históricos reales
                        </p>
                    </div>
                    <div v-else class="text-center py-12">
                        <AlertTriangle class="w-16 h-16 mx-auto mb-4 text-gray-400" />
                        <p class="text-gray-600 text-lg">No hay datos de predicción general disponibles</p>
                        <p class="text-gray-500 text-sm mt-2">Genere una nueva predicción para ver los datos</p>
                    </div>
                </div>
            </div>

            <!-- Predicción por Producto -->
            <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
                <div class="p-6 border-b border-gray-100">
                    <div class="flex flex-col lg:flex-row lg:justify-between lg:items-center gap-4">
                        <h3 class="text-xl font-semibold flex items-center gap-2">
                            <Brain class="w-6 h-6 text-purple-600" />
                            Predicción por Producto (Próximos 90 días)
                        </h3>
                        <div class="flex gap-2">
                            <button v-for="cat in ['A', 'B', 'C']" :key="cat" @click="selectedCategory = cat" :class="[
                                'px-4 py-2 rounded-lg text-sm font-medium transition-all duration-200 hover:shadow-md',
                                selectedCategory === cat
                                    ? 'bg-blue-600 text-white shadow-md'
                                    : 'bg-gray-100 text-gray-700 hover:bg-gray-200'
                            ]">
                                Categoría {{ cat }}
                                <span class="text-xs ml-1 opacity-75">
                                    {{ cat === 'A' ? '(Top)' : cat === 'B' ? '(Medio)' : '(Bajo)' }}
                                </span>
                            </button>
                        </div>
                    </div>
                </div>

                <div class="p-6">
                    <div v-if="Object.keys(productosFiltered).length === 0" class="text-center py-12">
                        <AlertTriangle class="w-16 h-16 mx-auto mb-4 text-gray-400" />
                        <p class="text-gray-600 text-lg">No hay productos en la categoría {{ selectedCategory }}</p>
                    </div>
                    <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                        <div v-for="(productos, id) in productosFiltered" :key="id"
                            class="p-4 border border-gray-200 rounded-lg hover:shadow-md transition-all duration-200 hover:border-gray-300">
                            <div v-for="producto in productos" :key="producto.id" class="mb-3 last:mb-0">
                                <h4 class="font-semibold text-gray-800 mb-2">{{ producto.nombre }}</h4>
                                <div class="space-y-2">
                                    <div class="flex items-center gap-2 text-sm text-gray-600">
                                        <TrendingUp class="w-4 h-4 text-blue-500" />
                                        <span>Predicción 90 días:</span>
                                        <span class="font-bold text-blue-600">{{ formatNumber(producto.prediccion)
                                            }}</span>
                                        <span>unidades</span>
                                    </div>
                                    <div>
                                        <span
                                            :class="['px-3 py-1 rounded-full text-xs font-medium border', getCategoryClass(producto.categoria)]">
                                            Categoría {{ producto.categoria }}
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Top Productos del Mes Anterior -->
            <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
                <div class="p-6 border-b border-gray-100">
                    <h3 class="text-xl font-semibold flex items-center gap-2">
                        <Award class="w-6 h-6 text-yellow-600" />
                        Top Productos ({{ mesAnterior }})
                    </h3>
                </div>

                <div class="p-6">
                    <div v-if="topProductos.length === 0" class="text-center py-12">
                        <AlertTriangle class="w-16 h-16 mx-auto mb-4 text-gray-400" />
                        <p class="text-gray-600 text-lg">No hay datos de ventas de productos disponibles</p>
                    </div>
                    <div v-else class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div v-for="(item, i) in topProductos" :key="i"
                            class="flex items-center gap-4 p-4 border border-gray-200 rounded-lg hover:shadow-md transition-all duration-200 hover:border-gray-300 group">
                            <div
                                :class="['w-12 h-12 rounded-full flex items-center justify-center text-lg font-bold shadow-md', getPositionClass(i)]">
                                {{ i + 1 }}
                            </div>
                            <div class="flex-1 min-w-0">
                                <h4
                                    class="font-semibold text-gray-800 truncate group-hover:text-blue-600 transition-colors">
                                    {{ item.nombre }}
                                </h4>
                                <p class="text-sm text-gray-600 mt-1">
                                    <span class="font-medium">{{ formatNumber(item.total_vendido) }}</span> unidades
                                    vendidas
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Productos Estacionales -->
            <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
                <div class="p-6 border-b border-gray-100">
                    <h3 class="text-xl font-semibold flex items-center gap-2">
                        <Calendar class="w-6 h-6 text-orange-600" />
                        Productos Estacionales
                    </h3>
                </div>

                <div class="p-6">
                    <div v-if="productosEstacionales.length === 0" class="text-center py-12">
                        <Calendar class="w-16 h-16 mx-auto mb-4 text-gray-400" />
                        <p class="text-gray-600 text-lg">No hay alertas estacionales disponibles</p>
                    </div>
                    <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                        <div v-for="(item, i) in productosEstacionales" :key="i"
                            class="p-4 bg-gradient-to-br from-orange-50 to-orange-100 border border-orange-200 rounded-lg hover:shadow-md transition-all duration-200">
                            <div class="flex items-start gap-3">
                                <Calendar class="w-5 h-5 text-orange-600 mt-1 flex-shrink-0" />
                                <div class="flex-1 min-w-0">
                                    <h4 class="font-semibold text-orange-800 mb-2 truncate">{{ item.nombre }}</h4>
                                    <p class="text-sm text-orange-600 mb-1">
                                        <span class="font-medium">Temporada:</span> {{ item.temporada }}
                                    </p>
                                    <p
                                        class="text-xs text-orange-700 bg-orange-100 px-2 py-1 rounded border border-orange-200">
                                        {{ item.alerta }}
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Alertas de Stock Crítico -->
            <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
                <div class="p-6 border-b border-gray-100">
                    <h3 class="text-xl font-semibold flex items-center gap-2">
                        <AlertTriangle class="w-6 h-6 text-red-600" />
                        Alertas de Stock Crítico
                    </h3>
                </div>

                <div class="p-6">
                    <div v-if="alertasStock.length === 0" class="text-center py-12">
                        <div class="flex items-center justify-center w-16 h-16 mx-auto mb-4 bg-green-100 rounded-full">
                            <Info class="w-8 h-8 text-green-600" />
                        </div>
                        <p class="text-green-700 text-lg font-medium">¡Todos los productos tienen stock suficiente!</p>
                        <p class="text-green-600 text-sm mt-2">No hay alertas críticas de inventario</p>
                    </div>
                    <div v-else class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div v-for="(item, i) in alertasStock" :key="i"
                            class="flex items-center gap-4 p-4 border-l-4 border-red-500 bg-red-50 rounded-lg hover:shadow-md transition-all duration-200">
                            <AlertTriangle class="w-8 h-8 text-red-600 flex-shrink-0" />
                            <div class="flex-1 min-w-0">
                                <h4 class="font-semibold text-red-800 mb-1 truncate">{{ item.nombre }}</h4>
                                <div class="space-y-1">
                                    <p class="text-sm text-red-700">
                                        <span class="font-medium">Stock actual:</span>
                                        <span class="font-bold">{{ item.stock_actual }}</span> /
                                        <span class="text-red-600">{{ item.stock_minimo }}</span>
                                    </p>
                                    <p class="text-xs text-red-600 bg-red-100 px-2 py-1 rounded border border-red-200">
                                        ⏰ {{ item.dias_restantes }} días restantes
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<style scoped>
/* Animaciones suaves para carga */
.animate-pulse {
    animation: pulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
}

@keyframes pulse {

    0%,
    100% {
        opacity: 1;
    }

    50% {
        opacity: .5;
    }
}

/* Estilos para gráficos */
img[alt*="Predicción"] {
    border-radius: 0.5rem;
    box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1);
}
</style>
