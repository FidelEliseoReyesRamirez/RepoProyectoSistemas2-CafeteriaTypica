<?php

namespace App\Http\Requests\Auth;

use Illuminate\Auth\Events\Lockout;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException; // ✅ IMPORTACIÓN LIMPIA

class LoginRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
        ];
    }

    public function authenticate(): void
    {
        $user = \App\Models\Usuario::where('email', $this->email)->first();

        // 🟥 BLOQUEO PERMANENTE
        if ($user && $user->bloqueado) {
            throw ValidationException::withMessages([
                'email' => 'Tu cuenta ha sido bloqueada por motivos de seguridad. Contacta con el administrador para restablecer tu acceso.',
            ]);
        }

        // 🕒 BLOQUEO TEMPORAL
        if ($user && $user->bloqueado_hasta && now()->lt($user->bloqueado_hasta)) {
            $minutos = number_format(now()->floatDiffInMinutes($user->bloqueado_hasta), 2);

            throw ValidationException::withMessages([
                'email' => "Demasiados intentos fallidos, tu cuenta ha sido bloqueada temporalmente. Vuelve a intentarlo en {$minutos} minutos.",
            ]);
        }

        // 🔄 Reestablecer bloqueos_hoy si es nuevo día
        if ($user && $user->updated_at && $user->updated_at->lt(now()->startOfDay())) {
            $user->bloqueos_hoy = 0;
            $user->save();
        }

        // 🟨 Intento de autenticación
        if (!Auth::attempt($this->only('email', 'password'))) {
            if ($user) {
                $user->increment('intentos_fallidos');

                if ($user->intentos_fallidos >= 4) {
                    $user->update([
                        'bloqueado_hasta' => now()->addMinutes(15),
                        'intentos_fallidos' => 0,
                        'bloqueos_hoy' => $user->bloqueos_hoy + 1,
                    ]);

                    if ($user->bloqueos_hoy >= 3) {
                        $user->update([
                            'bloqueado' => true,
                        ]);
                    }
                }
            }

            throw ValidationException::withMessages([
                'email' => trans('auth.failed'),
            ]);
        }

        // 🟢 Éxito
        if ($user) {
            $user->update([
                'intentos_fallidos' => 0,
                'bloqueado_hasta' => null,
            ]);
        }
    }

    public function ensureIsNotRateLimited(): void
    {
        if (!RateLimiter::tooManyAttempts($this->throttleKey(), 5)) {
            return;
        }

        event(new Lockout($this));

        $seconds = RateLimiter::availableIn($this->throttleKey());

        throw ValidationException::withMessages([
            'email' => trans('auth.throttle', [
                'seconds' => $seconds,
                'minutes' => ceil($seconds / 60),
            ]),
        ]);
    }

    public function throttleKey(): string
    {
        return Str::transliterate(Str::lower($this->string('email')) . '|' . $this->ip());
    }
}

