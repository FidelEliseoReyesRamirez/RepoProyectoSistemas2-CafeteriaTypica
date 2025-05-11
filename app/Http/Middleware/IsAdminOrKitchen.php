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

        if (!$user || !in_array($user->id_rol, [1, 5])) {
            abort(403, 'Acceso no autorizado.');
        }

        return $next($request);
    }
}
