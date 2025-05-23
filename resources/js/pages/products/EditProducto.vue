<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, useForm, usePage } from '@inertiajs/vue3';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Button } from '@/components/ui/button';
import InputError from '@/components/InputError.vue';
import type { PageProps } from '@/types';
import { ref } from 'vue';

interface Categoria {
    id_categoria: number;
    nombre: string;
}

const page = usePage<PageProps>();
const categorias = page.props.categorias as Categoria[];

const props = defineProps<{
    producto: {
        id_producto: number;
        nombre: string;
        descripcion: string;
        precio: number;
        id_categoria: number;
        imagen: string;
        disponibilidad: boolean;
    };
}>();

const form = useForm({
    nombre: props.producto.nombre,
    descripcion: props.producto.descripcion,
    precio: props.producto.precio.toString(),
    id_categoria: props.producto.id_categoria.toString(),
    imagen: props.producto.imagen,
    disponibilidad: props.producto.disponibilidad,
});

const errors = ref<{ [key: string]: string }>({});

const submit = () => {
    errors.value = {};

    if (!form.nombre) errors.value.nombre = 'El nombre es obligatorio.';
    if (!form.descripcion) errors.value.descripcion = 'La descripción es obligatoria.';
    if (!form.precio || parseFloat(form.precio as string) <= 0) {
        errors.value.precio = 'El precio debe ser mayor a 0.';
    }
    if (!form.id_categoria) errors.value.id_categoria = 'Selecciona una categoría.';
    if (!form.imagen || !form.imagen.startsWith('http')) {
        errors.value.imagen = 'Debe proporcionar una URL válida de imagen.';
    }

    if (Object.keys(errors.value).length > 0) return;

    form.put(`/productos/${props.producto.id_producto}`, {
        onSuccess: () => form.reset(),
    });
};
</script>

<template>
<AppLayout>
    <Head title="Editar Producto" />

    <div class="p-4 sm:p-6 text-[#4b3621] dark:text-white">
        <h1 class="text-xl sm:text-2xl font-bold mb-4 text-[#593E25] dark:text-[#d9a679]">
            Editar producto
        </h1>

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
                <Input v-model="form.precio" type="number" step="0.01" min="0.01" id="precio" />
                <InputError :message="form.errors.precio || errors.precio" />
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

            <div class="flex justify-end">
                <Button type="submit"
                        class="bg-[#a47148] hover:bg-[#8c5c3b] text-white px-6 py-2 rounded shadow">
                    Actualizar producto
                </Button>
            </div>
        </form>
    </div>
</AppLayout>
</template>
