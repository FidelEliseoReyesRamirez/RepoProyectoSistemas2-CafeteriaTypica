<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, useForm, usePage } from '@inertiajs/vue3';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Button } from '@/components/ui/button';
import InputError from '@/components/InputError.vue';
import type { PageProps } from '@/types';
import { ref } from 'vue';

interface Usuario {
  id_usuario: number;
  nombre: string;
  email: string;
  id_rol: number;
}

const page = usePage<PageProps>();
const roles = page.props.roles ?? [];

const props = defineProps<{
  usuario: Usuario;
}>();

const form = useForm({
  nombre: props.usuario.nombre,
  email: props.usuario.email,
  id_rol: props.usuario.id_rol,
});

const soloLetras = (e: KeyboardEvent) => {
  const letra = e.key;
  const regex = /^[A-Za-zÀ-ÿ ]$/;
  if (!regex.test(letra)) {
    e.preventDefault();
  }
};

const submit = () => {
  form.put(`/users/${props.usuario.id_usuario}`, {
    preserveScroll: true,
  });
};
</script>

<template>
  <AppLayout>
    <Head title="Edit User" />

    <div class="p-4 sm:p-6 text-[#4b3621] dark:text-white">
      <div class="flex flex-col sm:flex-row justify-between items-center mb-6 gap-2">
        <h1 class="text-xl sm:text-2xl font-bold text-[#593E25] dark:text-[#d9a679]">
          Edit User
        </h1>
      </div>

      <div
        class="bg-white dark:bg-[#1d1b16] rounded-xl border border-[#c5a880] dark:border-[#8c5c3b] p-4 sm:p-6 shadow-md max-w-4xl mx-auto w-full"
      >
        <form @submit.prevent="submit" class="grid gap-4 sm:gap-6">
          <div>
            <Label for="nombre">Full Name</Label>
            <Input
              id="nombre"
              v-model="form.nombre"
              type="text"
              maxlength="100"
              pattern="^[A-Za-zÀ-ÿ ]+$"
              title="Only letters and spaces. Max 100 characters."
              class="mt-1 block w-full"
              @keypress="soloLetras"
            />
            <InputError :message="form.errors.nombre" />
          </div>

          <div>
            <Label for="email">Email</Label>
            <Input
              id="email"
              v-model="form.email"
              type="email"
              class="mt-1 block w-full"
              title="Must be a valid Gmail address."
            />
            <InputError :message="form.errors.email" />
          </div>

          <div>
            <Label for="id_rol">Role</Label>
            <select
              v-model="form.id_rol"
              id="id_rol"
              class="mt-1 block w-full border rounded-md px-3 py-2 border-[#c5a880] dark:border-[#8c5c3b] bg-white dark:bg-[#26231d] text-[#4b3621] dark:text-white"
            >
              <option value="" disabled>Selecciona un rol</option>
              <option v-for="rol in roles" :key="rol.id_rol" :value="rol.id_rol">
                {{ rol.nombre }}
              </option>
            </select>
            <InputError :message="form.errors.id_rol" />
          </div>

          <div class="flex justify-end">
            <Button
              type="submit"
              class="bg-[#a47148] hover:bg-[#8c5c3b] text-white font-semibold py-2 px-6 rounded-lg shadow"
            >
              Editar
            </Button>
          </div>
        </form>
      </div>
    </div>
  </AppLayout>
</template>
