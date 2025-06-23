<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, useForm, usePage } from '@inertiajs/vue3';
import { router } from '@inertiajs/vue3';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Button } from '@/components/ui/button';
import InputError from '@/components/InputError.vue';
import { ref, onMounted } from 'vue';
import type { PageProps } from '@/types';

interface Rol {
    id_rol: number;
    nombre: string;
}

const page = usePage<PageProps>();
const roles = page.props.roles ?? [];
const authUser = page.props.auth.user;
const usuarioActualId = authUser?.id_usuario ?? 0;

const showPassword = ref(false);
const showConfirm = ref(false);
const showReminderModal = ref(true);

const togglePassword = () => (showPassword.value = !showPassword.value);
const toggleConfirm = () => (showConfirm.value = !showConfirm.value);

const generatePassword = () => {
    const letras = 'abcdefghijkmnopqrstuvwxyzABCDEFGHJKLMNPQRSTUVWXYZ';
    const numeros = '123456789';
    const simbolos = '!@#$%^&*';
    const todos = letras + numeros + simbolos;

    let pass = '';
    pass += letras[Math.floor(Math.random() * letras.length)];
    pass += numeros[Math.floor(Math.random() * numeros.length)];
    pass += simbolos[Math.floor(Math.random() * simbolos.length)];

    for (let i = 3; i < 10; i++) {
        pass += todos[Math.floor(Math.random() * todos.length)];
    }

    pass = pass.split('').sort(() => 0.5 - Math.random()).join('');

    form.contrasena = pass;
    form.contrasena_confirmation = pass;
};

const form = useForm({
    nombre: '',
    email: '',
    contrasena: '',
    contrasena_confirmation: '',
    id_rol: '',
});

const submit = () => {
    const password = form.contrasena;

    const tieneLetras = /[a-zA-Z]/.test(password);
    const tieneNumeros = /\d/.test(password);
    const tieneSimbolos = /[\W_]/.test(password);

    if (!tieneLetras || !tieneNumeros || !tieneSimbolos) {
        alert('La contraseña debe contener al menos una letra, un número y un símbolo.');
        return;
    }

    form.post('/users', {
        onSuccess: () => {
            router.visit('/users');
        },
        onFinish: () => form.reset('contrasena', 'contrasena_confirmation'),
    });
};


const soloLetras = (e: KeyboardEvent) => {
    const letra = e.key;
    const regex = /^[A-Za-zÀ-ÿ ]$/;
    if (!regex.test(letra)) {
        e.preventDefault();
    }
};


</script>

<template>
    <AppLayout>

        <Head title="Crear usuario" />

        <div class="p-4 sm:p-6 text-[#4b3621] dark:text-white">
            <div class="flex flex-col sm:flex-row sm:justify-between items-center mb-6 gap-2">
                <h1 class="text-xl sm:text-2xl font-bold text-[#593E25] dark:text-[#d9a679]">Crear nuevo usuario</h1>
            </div>

            <div
                class="bg-white dark:bg-[#1d1b16] rounded-xl border border-[#c5a880] dark:border-[#8c5c3b] p-4 sm:p-6 shadow-md max-w-4xl mx-auto w-full">
                <form @submit.prevent="submit" class="grid gap-4 sm:gap-6">
                    <div>
                        <Label for="nombre">Nombre Completo</Label>
                        <Input id="nombre" v-model="form.nombre" type="text" maxlength="100" pattern="^[A-Za-zÀ-ÿ ]+$"
                            title="Solo letras y espacios. Máximo 100 caracteres." class="mt-1 block w-full"
                            @keypress="soloLetras" />
                        <InputError :message="form.errors.nombre" />
                    </div>

                    <div>
                        <Label for="email">Correo electrónico</Label>
                        <Input id="email" v-model="form.email" type="email" class="mt-1 block w-full"
                            title="Debe ser un correo de Gmail válido (solo @gmail.com)." />
                        <InputError :message="form.errors.email" />
                    </div>

                    <div>
                        <Label for="contrasena">Contraseña</Label>
                        <div class="relative">
                            <Input :type="showPassword ? 'text' : 'password'" id="contrasena" v-model="form.contrasena"
                                class="mt-1 block w-full pr-10" title="Debe contener letras, números y símbolos." />
                            <button type="button" @click="togglePassword"
                                class="absolute inset-y-0 right-0 flex items-center px-3">
                                <img v-if="showPassword"
                                    src="https://www.svgrepo.com/show/390427/eye-password-see-view.svg" alt="ocultar"
                                    class="h-5 w-5" />
                                <img v-else
                                    src="https://www.svgrepo.com/show/390437/eye-key-look-password-security-see.svg"
                                    alt="mostrar" class="h-5 w-5" />
                            </button>
                        </div>
                        <InputError :message="form.errors.contrasena" />
                    </div>

                    <div>
                        <Label for="contrasena_confirmation">Confirmar contraseña</Label>
                        <div class="relative">
                            <Input :type="showConfirm ? 'text' : 'password'" id="contrasena_confirmation"
                                v-model="form.contrasena_confirmation" class="mt-1 block w-full pr-10" />
                            <button type="button" @click="toggleConfirm"
                                class="absolute inset-y-0 right-0 flex items-center px-3">
                                <img v-if="showConfirm"
                                    src="https://www.svgrepo.com/show/390427/eye-password-see-view.svg" alt="ocultar"
                                    class="h-5 w-5" />
                                <img v-else
                                    src="https://www.svgrepo.com/show/390437/eye-key-look-password-security-see.svg"
                                    alt="mostrar" class="h-5 w-5" />
                            </button>
                        </div>
                        <InputError :message="form.errors.contrasena_confirmation" />
                    </div>

                    <div>
                        <Label for="id_rol">Rol</Label>
                        <select v-model="form.id_rol" id="id_rol"
                            class="mt-1 block w-full border rounded-md px-3 py-2 border-[#c5a880] dark:border-[#8c5c3b] bg-white dark:bg-[#26231d] text-[#4b3621] dark:text-white">
                            <option value="" disabled>Seleccione un rol</option>
                            <option v-for="rol in roles" :key="rol.id_rol" :value="rol.id_rol">{{ rol.nombre }}</option>
                        </select>
                        <InputError :message="form.errors.id_rol" />
                    </div>

                    <div class="flex flex-col sm:flex-row justify-between gap-4">
                        <Button type="button" @click="generatePassword"
                            class="bg-[#6b4f3c] hover:bg-[#8c5c3b] text-white px-4 py-2 rounded shadow w-full sm:w-auto">
                            Generar contraseña
                        </Button>
                        <Button type="button" @click="router.visit('/users')"
                            class="bg-gray-500 hover:bg-red-600 text-white font-semibold py-2 px-6 rounded-lg shadow w-full sm:w-auto">
                            Cancelar
                        </Button>
                        <Button type="submit"
                            class="bg-[#a47148] hover:bg-[#8c5c3b] text-white font-semibold py-2 px-6 rounded-lg shadow w-full sm:w-auto">
                            Crear usuario
                        </Button>
                       
                    </div>
                </form>
            </div>
        </div>

        <!-- Modal recordatorio -->
        <div v-if="showReminderModal" class="fixed inset-0 bg-black/60 z-50 flex items-center justify-center">
            <div
                class="bg-white dark:bg-[#1e1c1a] p-6 rounded-lg shadow-xl max-w-sm w-full text-center border border-[#c5a880] dark:border-[#8c5c3b]">
                <h2 class="text-lg font-bold text-[#593E25] dark:text-[#d9a679] mb-2">⚠️ Importante</h2>
                <p class="text-sm text-[#4b3621] dark:text-white mb-4">
                    Recuerda guardar la contraseña generada. Una vez creado el usuario, no podrás volver a visualizarla.
                </p>
                <Button @click="showReminderModal = false" class="bg-[#a47148] text-white px-4 py-2 rounded w-full">
                    Entendido
                </Button>
            </div>
        </div>
    </AppLayout>
</template>
