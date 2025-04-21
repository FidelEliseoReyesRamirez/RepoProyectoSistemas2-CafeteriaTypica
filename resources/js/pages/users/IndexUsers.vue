<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import type { PageProps } from '@/types';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

const props = defineProps<{ usuarios: any[] }>();
const page = usePage<PageProps>();
const authUser = page.props.auth.user;

const showModal = ref(false);
const showError = ref(false);
const selectedUserId = ref<number | null>(null);
const selectedUserName = ref('');

// Modal para confirmar
const confirmEliminar = (id: number, nombre: string) => {
    selectedUserId.value = id;
    selectedUserName.value = nombre;

    if (!authUser) return;
    if (authUser.id_usuario === id) {
        showError.value = true;
        return;
    }

    showModal.value = true;
};

const cancelarEliminar = () => {
    showModal.value = false;
    showError.value = false;
    selectedUserId.value = null;
    selectedUserName.value = '';
};

const eliminarUsuario = () => {
    if (selectedUserId.value !== null) {
        router.delete(`/users/${selectedUserId.value}`, {
            onSuccess: () => cancelarEliminar(),
        });
    }
};
const desbloquearUsuario = (id: number) => {
    router.put(`/users/${id}/unblock`, {}, {
        onSuccess: () => router.reload(),
        onError: (errors) => console.error('Error al desbloquear:', errors),
    });
};

const filtro = ref('');

const usuariosFiltrados = computed(() => {
    if (!filtro.value) return props.usuarios;

    const termino = filtro.value.toLowerCase();
    return props.usuarios.filter(u =>
        u.nombre.toLowerCase().includes(termino) ||
        u.email.toLowerCase().includes(termino) ||
        (u.rol?.nombre || '').toLowerCase().includes(termino)
    );
});

</script>
<template>
    <AppLayout>

        <Head title="Users" />

        <div class="p-3 sm:p-6 text-[#4b3621] dark:text-white">
            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-2 sm:gap-4 mb-4">
                <div class="w-full">
                    <h1 class="text-lg sm:text-2xl font-bold text-[#593E25] dark:text-[#d9a679] mb-2">
                        Listado de Usuarios
                    </h1>

                    <input v-model="filtro" type="text" placeholder="Buscar por nombre, email o rol..."
                        class="w-full sm:max-w-sm border border-[#c5a880] dark:border-[#8c5c3b] rounded px-3 py-2 text-sm bg-white dark:bg-[#1d1b16] text-[#4b3621] dark:text-white" />
                </div>

                <div class="flex flex-col sm:flex-row gap-2 sm:gap-3 w-full sm:w-auto">
                    <Link href="/users/deleted"
                        class="bg-[#6b4f3c] hover:bg-[#8c5c3b] text-white text-xs sm:text-sm px-3 py-2 rounded shadow text-center">
                    Ver eliminados
                    </Link>
                    <Link href="/users/create"
                        class="bg-[#a47148] hover:bg-[#8c5c3b] text-white text-xs sm:text-sm px-3 py-2 rounded shadow text-center">
                    Crear Usuario
                    </Link>
                </div>
            </div>

            <div
                class="overflow-x-auto rounded-xl border border-[#c5a880] dark:border-[#8c5c3b] bg-white dark:bg-[#1d1b16]">
                <!-- Escritorio -->
                <table class="min-w-full text-xs sm:text-sm hidden sm:table">
                    <thead class="bg-neutral-100 dark:bg-neutral-800">
                        <tr>
                            <th class="px-2 py-2 text-left">#</th>
                            <th class="px-2 py-2 text-left">Nombre</th>
                            <th class="px-2 py-2 text-left">Email</th>
                            <th class="px-2 py-2 text-left">Rol</th>
                            <th class="px-2 py-2 text-left">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="(usuario, i) in usuariosFiltrados" :key="usuario.id_usuario"
                            class="border-t dark:border-neutral-700">
                            <td class="px-2 py-2">{{ i + 1 }}</td>
                            <td class="px-2 py-2">{{ usuario.nombre }}</td>
                            <td class="px-2 py-2">{{ usuario.email }}</td>
                            <td class="px-2 py-2">{{ usuario.rol?.nombre }}</td>
                            <td class="px-2 py-2 flex flex-wrap gap-2">
                                <Link :href="`/users/${usuario.id_usuario}/edit`"
                                    class="bg-blue-600 hover:bg-blue-700 text-white text-xs font-semibold py-1 px-2 rounded">
                                Editar
                                </Link>
                                <button @click="confirmEliminar(usuario.id_usuario, usuario.nombre)"
                                    class="bg-red-600 hover:bg-red-700 text-white text-xs font-semibold py-1 px-2 rounded">
                                    Eliminar
                                </button>
                                <button v-if="usuario.bloqueado" @click="desbloquearUsuario(usuario.id_usuario)"
                                    class="bg-yellow-500 hover:bg-yellow-600 text-white text-xs py-1 px-2 rounded">
                                    Desbloquear
                                </button>

                            </td>
                        </tr>
                    </tbody>
                </table>

                <!-- Móvil -->
                <div class="sm:hidden divide-y divide-[#d5c2a1] dark:divide-[#6c4f3c]">
                    <div v-for="(usuario, i) in usuarios" :key="usuario.id_usuario" class="px-3 py-3">
                        <p class="text-xs"><span class="font-semibold">#:</span> {{ i + 1 }}</p>
                        <p class="text-xs"><span class="font-semibold">Nombre:</span> {{ usuario.nombre }}</p>
                        <p class="text-xs"><span class="font-semibold">Email:</span> {{ usuario.email }}</p>
                        <p class="text-xs"><span class="font-semibold">Rol:</span> {{ usuario.rol?.nombre }}</p>
                        <div class="mt-2 flex flex-wrap gap-2">
                            <Link :href="`/users/${usuario.id_usuario}/edit`"
                                class="bg-blue-600 hover:bg-blue-700 text-white text-xs py-1 px-2 rounded">
                            Editar
                            </Link>
                            <button @click="confirmEliminar(usuario.id_usuario, usuario.nombre)"
                                class="bg-red-600 hover:bg-red-700 text-white text-xs py-1 px-2 rounded">
                                Eliminar
                            </button>
                            <button v-if="usuario.bloqueado" @click="desbloquearUsuario(usuario.id_usuario)"
                                class="bg-yellow-500 hover:bg-yellow-600 text-white text-xs py-1 px-2 rounded">
                                Desbloquear
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal Confirmación -->
        <div v-if="showModal" class="fixed inset-0 bg-black/40 flex items-center justify-center z-50 p-2 sm:p-4">
            <div class="bg-white dark:bg-[#2c211b] p-4 sm:p-6 rounded-lg w-full max-w-sm shadow-xl text-xs sm:text-sm">
                <h2 class="text-base sm:text-lg font-bold mb-3 text-[#593E25] dark:text-[#d9a679]">Confirmar eliminación
                </h2>
                <p class="mb-4">¿Estás seguro que deseas eliminar al usuario <strong>{{ selectedUserName }}</strong>?
                </p>
                <div class="flex flex-col sm:flex-row justify-end gap-2 sm:gap-4">
                    <button @click="cancelarEliminar" class="px-3 py-1.5 border rounded">Cancelar</button>
                    <button @click="eliminarUsuario" class="bg-red-600 text-white px-3 py-1.5 rounded hover:bg-red-700">
                        Eliminar
                    </button>
                </div>
            </div>
        </div>

        <!-- Modal Error -->
        <div v-if="showError" class="fixed inset-0 bg-black/40 flex items-center justify-center z-50 p-2 sm:p-4">
            <div class="bg-white dark:bg-[#2c211b] p-4 sm:p-6 rounded-lg w-full max-w-sm shadow-xl text-xs sm:text-sm">
                <h2 class="text-base sm:text-lg font-bold mb-3 text-red-700 dark:text-red-400">Acción no permitida</h2>
                <p class="mb-4">No puedes eliminar tu propio usuario o al último administrador.</p>
                <div class="flex justify-end">
                    <button @click="cancelarEliminar"
                        class="px-3 py-1.5 bg-[#a47148] text-white rounded hover:bg-[#8c5c3b]">
                        Cerrar
                    </button>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
