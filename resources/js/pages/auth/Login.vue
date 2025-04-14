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
            class="bg-card text-card-foreground backdrop-blur-sm px-6 py-8 sm:p-10 rounded-xl shadow-2xl w-full max-w-md font-serif border border-border">

            <div class="text-center mb-6 sm:mb-8">
                <!-- Logo -->
                <img src="https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcRCWbd36kwFq39_6G0gtnTmHpcqhGeDehsgJA&s"
                    alt="Logo Typica" class="h-50 w-50 mx-auto object-contain mix-blend-multiply mb-4" />


                <h1 class="text-2xl font-bold text-primary mt-4">BIENVENIDO</h1>
                <h2 class="text-xs text-muted-foreground mt-2">Empieza tu jornada con una buena gestión y mejor café
                </h2>
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
                            v-model="form.email"
                            class="rounded-md border border-border bg-background text-foreground" />
                        <InputError :message="form.errors.email" />
                    </div>

                    <!-- Password -->
                    <div class="grid gap-1.5">
                        <div class="flex items-center justify-between">
                            <Label for="password">Contraseña</Label>
                            <TextLink v-if="canResetPassword" :href="route('password.request')"
                                class="text-sm text-primary underline hover:text-foreground" :tabindex="5">
                                ¿Olvidaste tu contraseña?
                            </TextLink>
                        </div>
                        <Input id="password" type="password" required :tabindex="2" autocomplete="current-password"
                            v-model="form.password" placeholder="••••••••"
                            class="rounded-md border border-border bg-background text-foreground" />
                        <InputError :message="form.errors.password" />
                    </div>

                    <!-- Submit -->
                    <Button type="submit"
                        class="mt-4 w-full bg-primary text-primary-foreground hover:bg-primary/80 font-semibold py-2 rounded-lg shadow transition-all"
                        :tabindex="4" :disabled="form.processing">
                        <LoaderCircle v-if="form.processing" class="h-4 w-4 animate-spin mr-2" />
                        Iniciar sesión
                    </Button>
                </div>
            </form>
        </div>
    </div>
</template>