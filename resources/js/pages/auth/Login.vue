<script setup lang="ts">
import InputError from '@/components/InputError.vue';
import TextLink from '@/components/TextLink.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Head, useForm } from '@inertiajs/vue3';
import { LoaderCircle } from 'lucide-vue-next';
import { ref } from 'vue';

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

const showPassword = ref(false);
const togglePassword = () => {
  showPassword.value = !showPassword.value;
};
</script>

<template>
  <div
    class="min-h-screen bg-cover bg-center flex items-center justify-center px-4 sm:px-6"
    style="background-image: url('https://d1bvpoagx8hqbg.cloudfront.net/originals/experiencia-la-paz-bolivia-wara-c1c24d25cdb9259f80ae30ae679fee58.jpg');"
  >
    <Head title="Iniciar sesión" />

    <div
      class="bg-white text-[#4b3621] backdrop-blur-sm px-6 py-8 sm:p-10 rounded-xl shadow-2xl w-full max-w-md font-serif border border-[#c5a880]"
    >
      <!-- Logo -->
      <div class="text-center mb-6 sm:mb-8">
        <img
          src="https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcRCWbd36kwFq39_6G0gtnTmHpcqhGeDehsgJA&s"
          alt="Logo Typica"
          class="h-40 w-40 mx-auto object-contain mix-blend-multiply mb-4"
        />
        <h1 class="text-2xl font-bold text-[#593E25] mt-4">BIENVENIDO</h1>
        <h2 class="text-xs text-[#6e5846] mt-2">
          Empieza tu jornada con una buena gestión y mejor café
        </h2>
      </div>

      <div v-if="status" class="mb-4 text-center text-sm font-medium text-green-700">
        {{ status }}
      </div>

      <!-- Formulario -->
      <form @submit.prevent="submit" class="flex flex-col gap-6">
        <div class="grid gap-4">
          <!-- Email -->
          <div class="grid gap-1.5">
            <Label for="email">Correo electrónico</Label>
            <Input
              id="email"
              type="email"
              required
              autofocus
              :tabindex="1"
              autocomplete="email"
              v-model="form.email"
              class="rounded-md border border-[#c5a880] bg-white text-[#4b3621]"
            />
            <InputError :message="form.errors.email" />
          </div>

          <!-- Password con ícono -->
          <div class="grid gap-1.5">
            <div class="flex items-center justify-between">
              <Label for="password">Contraseña</Label>
              <a
                v-if="canResetPassword"
                :href="route('password.request')"
                class="text-sm underline"
                style="color: black; transition: color 0.2s;"
                @mouseover="$event.target.style.color = '#4b3621'"
                @mouseleave="$event.target.style.color = 'black'"
              >
                ¿Olvidaste tu contraseña?
              </a>
            </div>

            <div class="relative">
              <Input
                :type="showPassword ? 'text' : 'password'"
                id="password"
                v-model="form.password"
                required
                :tabindex="2"
                autocomplete="current-password"
                class="rounded-md border border-[#c5a880] bg-white text-[#4b3621] w-full pr-10"
              />
              <!-- Ícono ojo personalizado -->
              <button
                type="button"
                @click="togglePassword"
                class="absolute inset-y-0 right-0 flex items-center px-3"
              >
                <img
                  v-if="showPassword"
                  src="https://www.svgrepo.com/show/390427/eye-password-see-view.svg"
                  alt="ocultar"
                  class="h-5 w-5"
                />
                <img
                  v-else
                  src="https://www.svgrepo.com/show/390437/eye-key-look-password-security-see.svg"
                  alt="mostrar"
                  class="h-5 w-5"
                />
              </button>
            </div>
            <InputError :message="form.errors.password" />
          </div>

          <!-- Botón -->
          <Button
            type="submit"
            class="mt-4 w-full bg-[#a47148] hover:bg-[#8c5c3b] text-white font-semibold py-2 rounded-lg shadow-md transition-all"
            :tabindex="4"
            :disabled="form.processing"
          >
            <LoaderCircle v-if="form.processing" class="h-4 w-4 animate-spin mr-2" />
            Iniciar sesión
          </Button>
        </div>
      </form>
    </div>
  </div>
</template>
