<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use App\Models\User;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Inertia::share('auth', function () {
            if (Auth::check()) {
                $user = User::with('rol')->find(Auth::id());

                return [
                    'user' => [
                        'id_usuario' => $user->id_usuario,
                        'nombre' => $user->nombre,
                        'email' => $user->email,
                        'estado' => $user->estado,
                        'id_rol' => $user->id_rol,
                        'rol' => $user->rol ? [
                            'id_rol' => $user->rol->id_rol,
                            'nombre' => $user->rol->nombre,
                        ] : null,
                    ],
                ];
            }

            return ['user' => null];
        });
        Inertia::share('config', function () {
            return [
                'tiempo_cancelacion_minutos' => cache()->get('tiempo_cancelacion_minutos', 5),
            ];
        });
    }
}
