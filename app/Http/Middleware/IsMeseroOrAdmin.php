<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class IsMeseroOrAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (!$user) {
            return redirect()->route('login');
        }

        if (!in_array($user->id_rol, [1, 2])) {  // Permitir Admin (1) y Mesero (2)
            return redirect($this->rutaDisponiblePorRol($user->id_rol));  // Redirigir otros roles
        }

        return $next($request);
    }

    protected function rutaDisponiblePorRol(int $rol): string
    {
        return match ($rol) {
            3 => route('kitchen.view'),      // Redirigir Cocina (3)
            4 => route('cashier.orders'),    // Redirigir Cajero (4)
            default => '/',                  // Ruta fallback si no tiene rol válido
        };
    }
}
