<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class IsAdminOrKitchen
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (!$user) {
            return redirect()->route('login');
        }

        if (!in_array($user->id_rol, [1, 3])) {  // Solo permitir Admin (1) y Cocina (3)
            return redirect($this->rutaDisponiblePorRol($user->id_rol));  // Redirigir Mesero (2) y Cajero (4)
        }

        return $next($request);
    }

    protected function rutaDisponiblePorRol(int $rol): string
    {
        return match ($rol) {
            2 => route('order.index'),       // Redirigir Mesero (2)
            4 => route('cashier.orders'),    // Redirigir Cajero (4)
            default => '/',                  // Ruta fallback si no tiene rol válido
        };
    }
}
