<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class IsAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::user();

        if (!$user) {
            return redirect()->route('login');
        }

        // Bloquear el acceso a /dashboard y / para usuarios que no son admins
        if ($request->is('dashboard') || $request->is('/')) {
            if ($user->id_rol !== 1) {  // Si no es admin
                return redirect($this->rutaDisponiblePorRol($user->id_rol)); // Redirigir al primer módulo permitido según el rol
            }
        }

        // Si el usuario es admin, permite el acceso
        if ($user->id_rol === 1) {
            return $next($request); // Es admin, continúa
        }

        // Redirigir al primer módulo disponible según el rol
        return redirect($this->rutaDisponiblePorRol($user->id_rol));
    }

    protected function rutaDisponiblePorRol(int $rol): string
    {
        return match ($rol) {
            2 => route('order.index'),       // Mesero
            3 => route('productos.index'),   // Cocina
            4 => route('cashier.orders'),  
            default => '/',                  // Ruta fallback
        };
    }
}