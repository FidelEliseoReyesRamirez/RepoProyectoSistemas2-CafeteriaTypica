<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, useForm, usePage, router, Link } from '@inertiajs/vue3';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Button } from '@/components/ui/button';
import InputError from '@/components/InputError.vue';
import type { PageProps } from '@/types';
import { ref, watch } from 'vue';

const page = usePage<PageProps>();

interface Categoria {
    id_categoria: number;
    nombre: string;
}

const categorias = page.props.categorias as Categoria[];

const form = useForm({
    nombre: '',
    descripcion: '',
    precio: '',
    id_categoria: '',
    imagen: '',
    disponibilidad: true as boolean,
    cantidad_disponible: '',
});

const errors = ref<{ [key: string]: string }>({});
watch(() => form.precio, (val) => {
    // Reemplaza coma por punto
    let raw = val.replace(',', '.');

    // Elimina caracteres no válidos (solo números y un punto)
    raw = raw.replace(/[^0-9.]/g, '');

    // Solo un punto decimal permitido
    const parts = raw.split('.');
    if (parts.length > 2) {
        raw = parts[0] + '.' + parts[1];
    }

    // Truncar a dos decimales si hay decimales
    if (parts.length === 2) {
        parts[1] = parts[1].slice(0, 2); // corta después de 2 decimales
        raw = parts[0] + '.' + parts[1];
    }

    // Limitar a 10000 como máximo
    const parsed = parseFloat(raw);
    if (!isNaN(parsed)) {
        if (parsed > 10000) {
            form.precio = '10000.00';
        } else {
            form.precio = raw;
        }
    } else {
        form.precio = '';
    }
});



watch(() => form.cantidad_disponible, (val) => {
    let cantidad = parseInt(val);
    if (!isNaN(cantidad)) {
        if (cantidad <= 0) form.disponibilidad = false;
        if (cantidad > 1000) form.cantidad_disponible = '1000';
    } else {
        form.cantidad_disponible = '0';
    }
});

const submit = () => {
    errors.value = {};

    if (!form.nombre) errors.value.nombre = 'El nombre es obligatorio.';
    if (!form.descripcion) errors.value.descripcion = 'La descripción es obligatoria.';
    if (!form.precio || parseFloat(form.precio) <= 0 || parseFloat(form.precio) > 10000) {
        errors.value.precio = 'El precio debe ser mayor a 0 y hasta 10000.';
    }
    if (!form.id_categoria) errors.value.id_categoria = 'Selecciona una categoría.';
    if (!form.imagen || !form.imagen.startsWith('http')) {
        errors.value.imagen = 'Debe ser una URL válida.';
    }

    const cantidad = parseInt(form.cantidad_disponible);
    if (isNaN(cantidad) || cantidad < 0 || cantidad > 1000) {
        errors.value.cantidad_disponible = 'La cantidad debe ser entre 0 y 1000.';
    }

    if (cantidad === 0 && form.disponibilidad === true) {
        errors.value.disponibilidad = 'No puedes marcar como disponible si la cantidad es 0.';
    }

    if (Object.keys(errors.value).length > 0) return;

    router.post('/productos', form.data(), {
        onSuccess: () => form.reset(),
    });
};
const redondearPrecio = () => {
    let raw = form.precio.replace(',', '.');
    raw = raw.replace(/[^0-9.]/g, '');

    const parsed = parseFloat(raw);
    if (!isNaN(parsed)) {
        let redondeado = Math.round(parsed * 10) / 10;
        if (redondeado > 10000) redondeado = 10000;
        form.precio = redondeado.toFixed(2);
    } else {
        form.precio = '';
    }
};

</script>


<template>
    <AppLayout>

        <Head title="Crear Producto" />

        <div class="p-4 sm:p-6 text-[#4b3621] dark:text-white">
            <h1 class="text-xl sm:text-2xl font-bold mb-4 text-[#593E25] dark:text-[#d9a679]">Crear nuevo producto</h1>

            <form @submit.prevent="submit"
                class="bg-white dark:bg-[#1d1b16] border border-[#c5a880] dark:border-[#8c5c3b] rounded-xl p-6 max-w-4xl mx-auto space-y-4">

                <div>
                    <Label for="nombre">Nombre</Label>
                    <Input v-model="form.nombre" id="nombre" />
                    <InputError :message="form.errors.nombre || errors.nombre" />
                </div>

                <div>
                    <Label for="descripcion">Descripción</Label>
                    <textarea v-model="form.descripcion" id="descripcion" rows="3"
                        class="block w-full rounded border border-[#c5a880] dark:border-[#8c5c3b] bg-white dark:bg-[#26231d] text-[#4b3621] dark:text-white" />
                    <InputError :message="form.errors.descripcion || errors.descripcion" />
                </div>

                <div>
                    <Label for="precio">Precio (Bs)</Label>
                    <Input v-model="form.precio" id="precio" type="text" @keydown="(e: KeyboardEvent) => {
                        const allowedKeys = ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9', '.', 'Backspace', 'Tab', 'ArrowLeft', 'ArrowRight'];
                        const value = form.precio;

                        // Solo permitir un punto
                        if (e.key === '.' && value.includes('.')) {
                            e.preventDefault();
                        }

                        // Evitar caracteres no permitidos
                        if (!allowedKeys.includes(e.key)) {
                            e.preventDefault();
                        }

                        // No permitir más de 2 decimales
                        if (value.includes('.')) {
                            const decimalPart = value.split('.')[1] || '';
                            const cursorAtEnd = (e.target as HTMLInputElement).selectionStart === value.length;
                            if (decimalPart.length >= 2 && cursorAtEnd && !['Backspace', 'Tab', 'ArrowLeft', 'ArrowRight'].includes(e.key)) {
                                e.preventDefault();
                            }
                        }
                    }" @blur="redondearPrecio"/>
                    <InputError :message="form.errors.precio || errors.precio" />
                </div>

                <div>
                    <Label for="cantidad_disponible">Cantidad Disponible</Label>
                    <Input v-model="form.cantidad_disponible" type="text" id="cantidad_disponible" maxlength="4"
                        @keydown="(e: KeyboardEvent) => {
                            const invalid = ['e', 'E', '+', '-', '.', ','];
                            if (invalid.includes(e.key)) e.preventDefault();
                        }" />
                    <InputError :message="form.errors.cantidad_disponible || errors.cantidad_disponible" />
                </div>

                <div>
                    <Label for="id_categoria">Categoría</Label>
                    <select v-model="form.id_categoria" id="id_categoria"
                        class="block w-full border rounded-md px-3 py-2 border-[#c5a880] dark:border-[#8c5c3b] bg-white dark:bg-[#26231d] text-[#4b3621] dark:text-white">
                        <option value="" disabled>Selecciona una categoría</option>
                        <option v-for="cat in categorias" :key="cat.id_categoria" :value="cat.id_categoria">
                            {{ cat.nombre }}
                        </option>
                    </select>
                    <InputError :message="form.errors.id_categoria || errors.id_categoria" />
                </div>

                <div>
                    <Label for="imagen">Imagen (URL)</Label>
                    <Input v-model="form.imagen" id="imagen" type="text" placeholder="https://..." />
                    <InputError :message="form.errors.imagen || errors.imagen" />
                </div>

                <div>
                    <label class="flex items-center gap-2 text-sm">
                        <input type="checkbox" v-model="form.disponibilidad"
                            :disabled="parseInt(form.cantidad_disponible) === 0" />
                        <span>Disponible</span>
                    </label>
                    <InputError :message="errors.disponibilidad" />
                </div>

                <div class="flex justify-end gap-3">
                    <Link href="/productos"
                        class="px-4 py-2 rounded border border-gray-400 text-sm text-[#4b3621] dark:text-white dark:border-[#8c5c3b] hover:bg-gray-200 dark:hover:bg-[#2c211b]">
                    Cancelar
                    </Link>
                    <Button type="submit" class="bg-[#a47148] hover:bg-[#8c5c3b] text-white px-6 py-2 rounded shadow">
                        Guardar producto
                    </Button>
                </div>
            </form>
        </div>
    </AppLayout>
</template>
