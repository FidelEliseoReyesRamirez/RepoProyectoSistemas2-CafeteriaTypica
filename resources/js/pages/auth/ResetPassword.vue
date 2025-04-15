<script setup lang="ts">
import InputError from '@/components/InputError.vue';
import TextLink from '@/components/TextLink.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Head, useForm } from '@inertiajs/vue3';
import { LoaderCircle } from 'lucide-vue-next';

interface Props {
    token: string;
    email: string;
}

const props = defineProps<Props>();

const form = useForm({
    token: props.token,
    email: props.email,
    password: '',
    password_confirmation: '',
});

const submit = () => {
    form.post(route('password.store'), {
        onFinish: () => {
            form.reset('password', 'password_confirmation');
        },
    });
};
</script>

<template>
  <div class="relative min-h-screen bg-cover bg-center flex items-center justify-center px-4 sm:px-6"
       style="background-image: url('https://scontent.flpb1-1.fna.fbcdn.net/v/t39.30808-6/488271234_1070346118454390_8302317665730998860_n.jpg?_nc_cat=101&ccb=1-7&_nc_sid=cc71e4&_nc_ohc=1OZfSj_MM9AQ7kNvwE8HSz_&_nc_oc=AdmSQ6vn9jOUGbuJhB5LGpPINzSWtMY1N5nF8vvwRsXbumECKRB4qGE8XtGguzjfMJ8&_nc_zt=23&_nc_ht=scontent.flpb1-1.fna&_nc_gid=MKwj4d8Nv1KCQX-Bz-3n_A&oh=00_AfFP1p3dHLUOBZQ1LCzwUhNU6TN8fL5kfMNLDt8Szyk6Uw&oe=68037FC2');">
    
    <!-- Capa oscura -->
    <div class="absolute inset-0 bg-black/50 z-0"></div>

    <Head title="Restablecer contraseña" />

    <div class="relative z-10 bg-card text-card-foreground backdrop-blur-sm px-6 py-8 sm:p-10 rounded-xl shadow-2xl w-full max-w-md font-serif border border-border">

      <div class="text-center mb-6">
        <h1 class="text-2xl font-bold text-primary">Restablece tu contraseña</h1>
        <p class="text-sm text-muted-foreground mt-2">
          Ingresa tu nueva contraseña para continuar.
        </p>
      </div>

      <form @submit.prevent="submit" class="flex flex-col gap-6">
        <!-- Email (readonly) -->
        <div class="grid gap-2">
          <Label for="email">Correo electrónico</Label>
          <Input id="email" type="email" v-model="form.email" readonly
                 class="rounded-md border border-border bg-background text-foreground" />
          <InputError :message="form.errors.email" />
        </div>

        <!-- Nueva contraseña -->
        <div class="grid gap-2">
          <Label for="password">Nueva contraseña</Label>
          <Input id="password" type="password" autocomplete="new-password" v-model="form.password"
                 placeholder="••••••••" class="rounded-md border border-border bg-background text-foreground" />
          <InputError :message="form.errors.password" />
        </div>

        <!-- Confirmación -->
        <div class="grid gap-2">
          <Label for="password_confirmation">Confirmar contraseña</Label>
          <Input id="password_confirmation" type="password" autocomplete="new-password"
                 v-model="form.password_confirmation" placeholder="••••••••"
                 class="rounded-md border border-border bg-background text-foreground" />
          <InputError :message="form.errors.password_confirmation" />
        </div>

        <!-- Botón -->
        <Button type="submit" class="mt-4 w-full bg-primary text-primary-foreground hover:bg-primary/80 font-semibold py-2 rounded-lg shadow transition-all"
                :disabled="form.processing">
          <LoaderCircle v-if="form.processing" class="h-4 w-4 animate-spin mr-2" />
          Restablecer contraseña
        </Button>
      </form>

      <div class="mt-6 text-center text-sm text-muted-foreground">
        <span>¿Ya la habías cambiado?</span>
        <TextLink :href="route('login')" class="ml-1 underline text-primary hover:text-foreground">
          Iniciar sesión
        </TextLink>
      </div>
    </div>
  </div>
</template>
