<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, usePage } from '@inertiajs/vue3';
import PlaceholderPattern from '../components/PlaceholderPattern.vue';

const page = usePage() as any;
const user = page.props.auth?.user as any;

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: '/dashboard',
    },
];
</script>

<template>
    <Head title="Dashboard" />

    <AppLayout :breadcrumbs="breadcrumbs">


        <!-- MENSAJE DE BIENVENIDA -->
        <div class="mb-6 rounded-xl bg-gray-100 p-4 dark:bg-gray-800">
            <template v-if="user?.id_rol">
                <p v-if="user.id_rol === 1" class="text-xl font-semibold text-green-700 dark:text-green-400">
                    👑 Bienvenido Administrador {{ user.nombre }}
                </p>
                <p v-else-if="user.id_rol === 2" class="text-xl font-semibold text-blue-700 dark:text-blue-400">
                    📝 Bienvenido Mesero {{ user.nombre }}
                </p>
                <p v-else-if="user.id_rol === 3" class="text-xl font-semibold text-orange-700 dark:text-orange-400">
                    🍳 Bienvenido Cocina {{ user.nombre }}
                </p>
                <p v-else-if="user.id_rol === 4" class="text-xl font-semibold text-purple-700 dark:text-purple-400">
                    💰 Bienvenido Cajero {{ user.nombre }}
                </p>
                <p v-else class="text-xl font-semibold text-gray-700 dark:text-gray-300">
                    🎉 Bienvenido {{ user.nombre }}
                </p>
            </template>

            <template v-else>
                <p class="text-red-600 dark:text-red-400">⚠️ No se encontró el rol del usuario</p>
            </template>
        </div>

        <!-- CONTENIDO DEL DASHBOARD -->
        <div class="flex h-full flex-1 flex-col gap-4 rounded-xl p-4">
            <div class="grid auto-rows-min gap-4 md:grid-cols-3">
                <div class="relative aspect-video overflow-hidden rounded-xl border border-sidebar-border/70 dark:border-sidebar-border">
                    <PlaceholderPattern />
                </div>
                <div class="relative aspect-video overflow-hidden rounded-xl border border-sidebar-border/70 dark:border-sidebar-border">
                    <PlaceholderPattern />
                </div>
                <div class="relative aspect-video overflow-hidden rounded-xl border border-sidebar-border/70 dark:border-sidebar-border">
                    <PlaceholderPattern />
                </div>
            </div>
            <div class="relative min-h-[100vh] flex-1 rounded-xl border border-sidebar-border/70 dark:border-sidebar-border md:min-h-min">
                <PlaceholderPattern />
            </div>
        </div>
    </AppLayout>
</template>
