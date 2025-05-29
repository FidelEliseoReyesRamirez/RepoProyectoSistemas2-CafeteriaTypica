<script setup lang="ts">
import NavMain from '@/components/NavMain.vue';
import NavUser from '@/components/NavUser.vue';
import {
  Sidebar,
  SidebarContent,
  SidebarFooter,
  SidebarHeader,
  SidebarMenu,
  SidebarMenuButton,
  SidebarMenuItem
} from '@/components/ui/sidebar';
import { type NavItem } from '@/types';
import { Link, usePage } from '@inertiajs/vue3';
import {
  LayoutGrid,
  UserPlus,
  Coffee,
  Utensils,
  Settings,
  ListChecks,
  DollarSign,
  ChefHat,
  NotebookPen // Nuevo ícono agregado
} from 'lucide-vue-next';
import AppLogo from './AppLogo.vue';
import type { PageProps } from '@/types';

const page = usePage<PageProps>();
const authUser = page.props.auth.user;

const mainNavItems: NavItem[] = [
  ...(authUser && authUser.id_rol === 1 ? [{
    title: 'Dashboard',
    href: '/dashboard',
    icon: LayoutGrid,
  }] : []),

  ...(authUser && authUser.id_rol === 1 ? [{
    title: 'Usuarios',
    href: route('users.index'),
    icon: UserPlus,
  }] : []),

  ...(authUser && authUser.id_rol === 1 ? [{
    title: 'Configuración',
    href: '/config',
    icon: Settings,
  }] : []),

  ...(authUser && [1, 3].includes(authUser.id_rol) ? [{
    title: 'Productos',
    href: route('productos.index'),
    icon: Utensils,
  }] : []),

  ...(authUser && [1, 2].includes(authUser.id_rol) ? [{
    title: 'Ordenar',
    href: route('order.index'),
    icon: Coffee,
  }] : []),

  ...(authUser && authUser.id_rol === 1 ? [{
    title: 'Pedidos',
    href: '/all-orders',
    icon: ListChecks,
  }] : []),

  ...(authUser && [1, 4].includes(authUser.id_rol) ? [{
    title: 'Caja',
    href: '/cashier-orders',
    icon: DollarSign,
  }] : []),

  ...(authUser && [1, 3].includes(authUser.id_rol) ? [{
    title: 'Cocina',
    href: '/kitchen-orders',
    icon: ChefHat,
  }] : []),

  // Nuevo ítem con ícono de blog de notas
  ...(authUser ? [{
    title: 'Auditoria',
    href: '/audit',
    icon: NotebookPen,
  }] : []),
];

// 👉 redirigir el logo al primer ítem permitido
const firstAvailableHref = mainNavItems.length > 0 ? mainNavItems[0].href : '/';
</script>

<template>
  <Sidebar collapsible="icon" variant="inset">
    <SidebarHeader>
      <SidebarMenu>
        <SidebarMenuItem>
          <SidebarMenuButton size="lg" as-child>
            <Link :href="firstAvailableHref">
              <AppLogo />
            </Link>
          </SidebarMenuButton>
        </SidebarMenuItem>
      </SidebarMenu>
    </SidebarHeader>

    <SidebarContent>
      <NavMain :items="mainNavItems" />
    </SidebarContent>

    <SidebarFooter>
      <NavUser />
    </SidebarFooter>
  </Sidebar>

  <slot />
</template>
