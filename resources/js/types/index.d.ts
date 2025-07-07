import type { PageProps as InertiaPageProps } from '@inertiajs/core';
import type { LucideIcon } from 'lucide-vue-next';
import type { Config } from 'ziggy-js';

export interface Rol {
  id_rol: number;
  nombre: string;
}

export interface PageProps extends InertiaPageProps {
  auth: {
    user: User | null;
  };
  roles?: Rol[];
  config?: {
    estados_cancelables?: string[];
    estados_editables?: string[];
    tiempo_cancelacion_minutos?: number;
    tiempo_edicion_minutos?: number;
    tiempos_por_estado?: {
      [estado: string]: {
        cancelar: number;
        editar: number;
      };
    };
    horario_atencion?: {
      [dia: string]: {
        hora_inicio: string;
        hora_fin: string;
      };
    };
  };
  now?: string;
};




export interface User {
  id_usuario: number;
  name: string;
  email: string;
  avatar?: string;
  email_verified_at: string | null;
  created_at: string;
  updated_at: string;
  id_rol: number;
}

export interface Auth {
  user: User;
}

export interface BreadcrumbItem {
  title: string;
  href: string;
}

export type BreadcrumbItemType = BreadcrumbItem;

export interface NavItem {
  title: string;
  href: string;
  icon?: LucideIcon;
  isActive?: boolean;
}

export interface SharedData extends PageProps {
  name: string;
  quote: {
    message: string;
    author: string;
  };
  ziggy: Config & { location: string };
}
