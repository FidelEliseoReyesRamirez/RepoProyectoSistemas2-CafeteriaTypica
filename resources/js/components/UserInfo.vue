<script setup lang="ts">
import type { User } from '@/types';

interface Props {
    user: User;
    showEmail?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
    showEmail: false,
});
</script>

<template>
    <div class="flex items-center gap-2 text-sm text-foreground">
        <!-- Ícono de usuario SVG -->
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"
            class="h-7 w-7 fill-[#593E25] dark:fill-[#D9A679]">
            <path d="M256,256A128,128,0,1,0,128,128,128,128,0,0,0,256,256Zm89.6,32H338.4a174.6,174.6,0,0,1-164.8,0H166.4A118.4,118.4,0,0,0,48,406.4V432a48,48,0,0,0,48,48H416a48,48,0,0,0,48-48V406.4A118.4,118.4,0,0,0,345.6,288Z"/>
        </svg>

        <!-- Nombre del usuario -->
        <span class="font-medium truncate">{{ props.user?.nombre ?? 'Desconocido' }}</span>
    </div>
</template>
