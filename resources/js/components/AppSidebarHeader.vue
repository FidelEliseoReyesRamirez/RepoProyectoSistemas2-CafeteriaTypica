<script setup lang="ts">
import Breadcrumbs from '@/components/Breadcrumbs.vue';
import { SidebarTrigger } from '@/components/ui/sidebar';
import type { BreadcrumbItemType } from '@/types';
import { usePage } from '@inertiajs/vue3';
import { ref, onMounted } from 'vue';

defineProps<{
    breadcrumbs?: BreadcrumbItemType[];
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
    <header
        class="flex h-16 shrink-0 items-center gap-2 border-b border-sidebar-border/70 px-6 transition-[width,height] ease-linear group-has-[[data-collapsible=icon]]/sidebar-wrapper:h-12 md:px-4"
    >
        <div class="flex items-center gap-2 flex-1">
            <SidebarTrigger class="-ml-1" />
            <template v-if="breadcrumbs.length > 0">
                <Breadcrumbs :breadcrumbs="breadcrumbs" />
            </template>
        </div>

        <h1 v-if="rolNombre" class="text-sm font-semibold text-sidebar-foreground">
            Rol: {{ rolNombre }}
        </h1>
    </header>
</template>
