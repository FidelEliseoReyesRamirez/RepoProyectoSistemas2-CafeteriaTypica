<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class IsAdminOrCashier
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (!$user) {
            return redirect()->route('login');
        }

        if (!in_array($user->id_rol, [1, 4])) {  // Permitir Admin (1) y Cajero (4)
            return redirect($this->rutaDisponiblePorRol($user->id_rol));  // Redirigir otros roles
        }

        return $next($request);
    }

    protected function rutaDisponiblePorRol(int $rol): string
    {
        return match ($rol) {
            2 => route('order.index'),       // Redirigir Mesero (2)
            3 => route('kitchen.view'),      // Redirigir Cocina (3)
            default => '/',                  // Ruta fallback si no tiene rol válido
        };
    }
}
