<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class NoDirectAccess
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $referer = $request->headers->get('referer');
        $host = $request->getSchemeAndHttpHost();

        if (!$referer || strpos($referer, $host) !== 0) {
            abort(403, 'Acceso directo no permitido.');
        }

        return $next($request);
    }
}
