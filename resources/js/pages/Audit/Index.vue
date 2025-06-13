<script setup lang="ts">
import { Head } from '@inertiajs/vue3'
import AppLayout from '@/layouts/AppLayout.vue'
</script>

<template>
  <AppLayout>
    <Head title="Auditoría del sistema" />

    <div class="p-4 flex flex-col gap-6 text-[#4b3621] dark:text-white">
      <div class="rounded-xl p-4 border border-[#c5a880] dark:border-[#8c5c3b] space-y-6">
        <h1 class="text-2xl font-bold">Auditoría del sistema</h1>
        <p class="text-gray-600 dark:text-gray-300">Aquí se mostrarán los registros de auditoría del sistema.</p>

        <!-- Aquí irán tus filtros, tabla de registros, y botones de exportación -->
        <div class="text-center">
          <p class="text-sm italic text-gray-500 dark:text-gray-400">Módulo en construcción...</p>
        </div>
      </div>
    </div>
  </AppLayout>
</template>
