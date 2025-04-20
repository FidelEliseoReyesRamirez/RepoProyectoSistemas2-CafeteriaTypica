<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Inertia\Response;
use App\Models\Logseguridad;
use App\Models\User;

class AuthenticatedSessionController extends Controller
{
    /**
     * Show the login page.
     */
    public function create(Request $request): Response
    {
        return Inertia::render('auth/Login', [
            'canResetPassword' => Route::has('password.request'),
            'status' => $request->session()->get('status'),
        ]);
    }

    /**
     * Handle an incoming authentication request.
     */
    public function store(LoginRequest $request): RedirectResponse
    {
        $user = User::where('email', $request->email)->first();

        try {
            // ⛔ Verifica bloqueos (esto puede lanzar ValidationException)
            $request->ensureIsNotRateLimited();
            $request->authenticate();
        } catch (\Illuminate\Validation\ValidationException $e) {
            // Si el usuario existe, se registra intento fallido
            if ($user) {
                Logseguridad::create([
                    'id_usuario' => $user->id_usuario,
                    'evento' => 'Login fallido',
                    'descripcion' => 'Intento fallido de inicio de sesión por el usuario: ' . $user->nombre . '.',
                    'fecha_evento' => now(),
                ]);
            } else {
                // Usuario no encontrado
                Logseguridad::create([
                    'id_usuario' => null,
                    'evento' => 'Login fallido',
                    'descripcion' => 'Intento fallido de inicio de sesión con correo no registrado: ' . $request->email,
                    'fecha_evento' => now(),
                ]);
            }

            throw $e;
        }

        // ✅ Autenticación correcta
        $request->session()->regenerate();

        Logseguridad::create([
            'id_usuario' => $user->id_usuario,
            'evento' => 'Login exitoso',
            'descripcion' => 'El usuario: ' . $user->nombre . ' inició sesión correctamente.',
            'fecha_evento' => now(),
        ]);

        return redirect()->intended(route('dashboard', absolute: false));
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $user = Auth::user();

        if ($user) {
            Logseguridad::create([
                'id_usuario' => $user->id_usuario,
                'evento' => 'Logout',
                'descripcion' => 'El usuario ' . $user->nombre . ' cerró sesión.',
                'fecha_evento' => now(),
            ]);
        }

        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }
}
