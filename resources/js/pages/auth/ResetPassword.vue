<script setup lang="ts">
import InputError from '@/components/InputError.vue';
import TextLink from '@/components/TextLink.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Head, useForm } from '@inertiajs/vue3';
import { LoaderCircle } from 'lucide-vue-next';
import { ref } from 'vue';

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

const showPassword = ref(false);
const showConfirm = ref(false);

const togglePassword = () => {
  showPassword.value = !showPassword.value;
};

const toggleConfirm = () => {
  showConfirm.value = !showConfirm.value;
};

const submit = () => {
  form.post(route('password.store'), {
    onFinish: () => form.reset('password', 'password_confirmation'),
  });
};
</script>

<template>
  <div
    class="relative min-h-screen bg-cover bg-center flex items-center justify-center px-4 sm:px-6"
    style="background-image: url('https://emprendimientosbolivia.com/wp-content/uploads/2024/01/SG_9707.jpg');"
  >
    <div class="absolute inset-0 bg-black/50 z-0"></div>

    <Head title="Restablecer contraseña" />

    <div
      class="relative z-10 bg-white text-[#4b3621] backdrop-blur-sm px-6 py-8 sm:p-10 rounded-xl shadow-2xl w-full max-w-md font-serif border border-[#c5a880]"
    >
      <div class="text-center mb-6">
        <h1 class="text-2xl font-bold text-[#593E25]">Restablece tu contraseña</h1>
        <p class="text-sm text-[#6e5846] mt-2">Ingresa tu nueva contraseña para continuar.</p>
      </div>

      <form @submit.prevent="submit" class="flex flex-col gap-6">
        <!-- Email (readonly) -->
        <div class="grid gap-2">
          <Label for="email">Correo electrónico</Label>
          <Input
            id="email"
            type="email"
            v-model="form.email"
            readonly
            class="rounded-md border border-[#c5a880] bg-white text-[#4b3621]"
          />
          <InputError :message="form.errors.email" />
        </div>

        <!-- Nueva contraseña -->
        <div class="grid gap-2">
          <Label for="password">Nueva contraseña</Label>
          <div class="relative">
            <Input
              :type="showPassword ? 'text' : 'password'"
              id="password"
              v-model="form.password"
              autocomplete="new-password"
              class="rounded-md border border-[#c5a880] bg-white text-[#4b3621] pr-10 w-full"
            />
            <button
              type="button"
              @click="togglePassword"
              class="absolute inset-y-0 right-0 flex items-center px-3 text-[#4b3621] hover:text-[#8c5c3b]"
            >
              <img
                v-if="showPassword"
                src="https://www.svgrepo.com/show/390427/eye-password-see-view.svg"
                alt="Ocultar"
                class="h-5 w-5"
              />
              <img
                v-else
                src="https://www.svgrepo.com/show/390437/eye-key-look-password-security-see.svg"
                alt="Mostrar"
                class="h-5 w-5"
              />
            </button>
          </div>
          <InputError :message="form.errors.password" />
        </div>

        <!-- Confirmación -->
        <div class="grid gap-2">
          <Label for="password_confirmation">Confirmar contraseña</Label>
          <div class="relative">
            <Input
              :type="showConfirm ? 'text' : 'password'"
              id="password_confirmation"
              v-model="form.password_confirmation"
              autocomplete="new-password"
              class="rounded-md border border-[#c5a880] bg-white text-[#4b3621] pr-10 w-full"
            />
            <button
              type="button"
              @click="toggleConfirm"
              class="absolute inset-y-0 right-0 flex items-center px-3 text-[#4b3621] hover:text-[#8c5c3b]"
            >
              <img
                v-if="showConfirm"
                src="https://www.svgrepo.com/show/390427/eye-password-see-view.svg"
                alt="Ocultar"
                class="h-5 w-5"
              />
              <img
                v-else
                src="https://www.svgrepo.com/show/390437/eye-key-look-password-security-see.svg"
                alt="Mostrar"
                class="h-5 w-5"
              />
            </button>
          </div>
          <InputError :message="form.errors.password_confirmation" />
        </div>

        <!-- Botón -->
        <Button
          type="submit"
          class="mt-4 w-full bg-[#a47148] hover:bg-[#8c5c3b] text-white font-semibold py-2 rounded-lg shadow-md transition-all"
          :disabled="form.processing"
        >
          <LoaderCircle v-if="form.processing" class="h-4 w-4 animate-spin mr-2" />
          Restablecer contraseña
        </Button>
      </form>

      <div class="mt-6 text-center text-sm text-[#6e5846]">
        <span>¿Ya la habías cambiado?</span>
        <TextLink
          :href="route('login')"
          class="ml-1 underline"
          style="color: black"
          @mouseover="$event.target.style.color = '#a47148'"
          @mouseleave="$event.target.style.color = 'black'"
        >
          Iniciar sesión
        </TextLink>
      </div>
    </div>
  </div>
</template>
