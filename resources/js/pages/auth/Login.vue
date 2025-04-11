<script setup lang="ts">
import InputError from '@/components/InputError.vue';
import TextLink from '@/components/TextLink.vue';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Head, useForm } from '@inertiajs/vue3';
import { LoaderCircle } from 'lucide-vue-next';

defineProps<{
    status?: string;
    canResetPassword: boolean;
}>();

const form = useForm({
    email: '',
    password: '',
    remember: false,
});

const submit = () => {
    form.post(route('login'), {
        onFinish: () => form.reset('password'),
    });
};
</script>

<template>
    <div class="min-h-screen bg-cover bg-center flex items-center justify-center px-4 sm:px-6"
        style="background-image: url('https://d1bvpoagx8hqbg.cloudfront.net/originals/experiencia-la-paz-bolivia-wara-c1c24d25cdb9259f80ae30ae679fee58.jpg');">

        <Head title="Iniciar sesión" />

        <div
            class="bg-white/100 backdrop-blur-sm px-6 py-8 sm:p-10 rounded-xl shadow-2xl w-full max-w-md text-[#4b3621] font-serif">
            <div class="text-center mb-6 sm:mb-8">
                <!-- Logo -->
                <img src="https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcRCWbd36kwFq39_6G0gtnTmHpcqhGeDehsgJA&s"
                    alt="Typica Logo" class="mx-auto h-16 w-16 sm:h-20 sm:w-20 object-contain mb-3 sm:mb-4" />
                <h1 class="text-2xl sm:text-3xl font-bold tracking-wide">TYPICA</h1>
                <p class="text-sm text-[#6e5846] mt-1">Bienvenido al sistema de la cafetería</p>
            </div>

            <div v-if="status" class="mb-4 text-center text-sm font-medium text-green-700">
                {{ status }}
            </div>

            <form @submit.prevent="submit" class="flex flex-col gap-6">
                <div class="grid gap-4">
                    <!-- Email -->
                    <div class="grid gap-1.5">
                        <Label for="email">Correo electrónico</Label>
                        <Input id="email" type="email" required autofocus :tabindex="1" autocomplete="email"
                            v-model="form.email" class="rounded-md border border-[#c5a880] bg-white text-[#4b3621]" />
                        <InputError :message="form.errors.email" />
                    </div>

                    <!-- Password -->
                    <div class="grid gap-1.5">
                        <div class="flex items-center justify-between">
                            <Label for="password">Contraseña</Label>
                            <TextLink v-if="canResetPassword" :href="route('password.request')"
                                class="text-sm text-black text-opacity-100 underline hover:text-[#4b3621]"
                                :tabindex="5">
                                ¿Olvidaste tu contraseña?
                            </TextLink>

                        </div>
                        <Input id="password" type="password" required :tabindex="2" autocomplete="current-password"
                            v-model="form.password" placeholder="••••••••"
                            class="rounded-md border border-[#c5a880] bg-white text-[#4b3621]" />
                        <InputError :message="form.errors.password" />
                    </div>

                    <!-- Remember -->
                    <!-- <div class="flex items-center space-x-2 mt-1">
                        <Checkbox id="remember" v-model:checked="form.remember" :tabindex="4" />
                        <Label for="remember">Recordarme</Label>
                    </div>-->

                    <!-- Submit -->
                    <Button type="submit"
                        class="mt-4 w-full bg-[#a47148] hover:bg-[#8c5c3b] text-white font-semibold py-2 rounded-lg shadow-md transition-all"
                        :tabindex="4" :disabled="form.processing">
                        <LoaderCircle v-if="form.processing" class="h-4 w-4 animate-spin mr-2" />
                        Iniciar sesión
                    </Button>
                </div>
            </form>
        </div>
    </div>
</template>
