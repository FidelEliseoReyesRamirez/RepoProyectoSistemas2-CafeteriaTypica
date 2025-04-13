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
        <!-- Ícono de tacita de café -->
        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-primary" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                d="M17 8h2a2 2 0 110 4h-2m0-4v4m0 0v1a5 5 0 11-10 0V8h10zM6 19h12" />
        </svg>

        <!-- Nombre del usuario -->
        <span class="font-medium truncate">{{ props.user?.nombre ?? 'Desconocido' }}</span>
    </div>
</template>
