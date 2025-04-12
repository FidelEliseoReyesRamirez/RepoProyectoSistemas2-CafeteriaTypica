<script setup lang="ts">
import { ref, onMounted } from 'vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, usePage } from '@inertiajs/vue3';
import PlaceholderPattern from '../components/PlaceholderPattern.vue';
import type { BreadcrumbItem } from '@/types';

defineProps<{
  breadcrumbs?: BreadcrumbItem[];
}>();
const page = usePage() as any;
const user = page.props.auth?.user as any;

const rolNombre = ref<string | null>(null);

onMounted(async () => {
    if (user?.id_rol) {
        try {
            const response = await fetch(`/rol/${user.id_rol}`);
            const data = await response.json();
            rolNombre.value = data.nombre;
        } catch (error) {
            console.error('Error al obtener el rol:', error);
        }
    }
});
</script>
<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="mb-6 rounded-xl bg-gray-100 p-4 dark:bg-gray-800">
            <template v-if="rolNombre">
                <p class="text-xl font-semibold text-gray-800 dark:text-white">
                    🎉 Bienvenido {{ rolNombre }} {{ user.nombre }}
                </p>
            </template>
        </div>
    </AppLayout>
</template>
