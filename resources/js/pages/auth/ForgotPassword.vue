<script setup lang="ts">
import InputError from '@/components/InputError.vue';
import TextLink from '@/components/TextLink.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Head, useForm } from '@inertiajs/vue3';
import { LoaderCircle } from 'lucide-vue-next';

defineProps<{
    status?: string;
}>();

const form = useForm({
    email: '',
});

const submit = () => {
    form.post(route('password.email'));
};
</script>

<template>
    <div class="relative min-h-screen bg-cover bg-center flex items-center justify-center px-4 sm:px-6"
         style="background-image: url('https://scontent.flpb1-1.fna.fbcdn.net/v/t39.30808-6/481669738_1046453534176982_1356778312309744329_n.jpg?_nc_cat=100&ccb=1-7&_nc_sid=127cfc&_nc_ohc=2NuuRGlIumUQ7kNvwFS28qw&_nc_oc=AdnqMo4nj-PVaUcQjy0w_IkktszHl81WnVn6GsBREMlWQU3xhmnNS9iUv5ZUt1VfdQc&_nc_zt=23&_nc_ht=scontent.flpb1-1.fna&_nc_gid=Y9SRmCoEifhsbevmbCIZOw&oh=00_AfHvCLr3737bDfDVJCrTKM_PV5N-mYocAKFRVtrRMWxT-A&oe=68036867');">
  
      <!-- Capa oscura encima del fondo -->
      <div class="absolute inset-0 bg-black/50 z-0"></div>
  
      <Head title="Recuperar contraseña" />
  
      <!-- Contenido principal por encima de la capa -->
      <div
        class="relative z-10 bg-card text-card-foreground backdrop-blur-sm px-6 py-8 sm:p-10 rounded-xl shadow-2xl w-full max-w-md font-serif border border-border">
  
        <div class="text-center mb-6">
          <h1 class="text-2xl font-bold text-primary">¿Olvidaste tu contraseña?</h1>
          <p class="text-sm text-muted-foreground mt-2">
            Ingresa tu correo electrónico y te enviaremos un enlace para restablecer tu contraseña.
          </p>
        </div>
  
        <div v-if="status" class="mb-4 text-center text-sm font-medium text-green-600">
          {{ status }}
        </div>
  
        <form @submit.prevent="submit" class="flex flex-col gap-6">
          <div class="grid gap-2">
            <Label for="email">Correo electrónico</Label>
            <Input id="email" type="email" name="email" autocomplete="off" v-model="form.email" autofocus
                   class="rounded-md border border-border bg-background text-foreground" />
            <InputError :message="form.errors.email" />
          </div>
  
          <div class="mt-6">
            <Button class="w-full bg-primary text-primary-foreground hover:bg-primary/80 font-semibold py-2 rounded-lg shadow transition-all"
                    :disabled="form.processing">
              <LoaderCircle v-if="form.processing" class="h-4 w-4 animate-spin mr-2" />
              Enviar enlace de recuperación
            </Button>
          </div>
        </form>
  
        <div class="mt-6 text-center text-sm text-muted-foreground">
          <span>¿Ya recuerdas tu contraseña?</span>
          <TextLink :href="route('login')" class="ml-1 underline text-primary hover:text-foreground">
            Iniciar sesión
          </TextLink>
        </div>
      </div>
    </div>
  </template>
  
