<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\Rol;
use App\Models\Usuario;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use App\Models\Auditorium;
use Illuminate\Support\Facades\Auth;



class UsuarioController extends Controller
{
    public function index()
    {
        $usuarios = Usuario::with('rol')
            ->where('eliminado', 0)
            ->get();

        return Inertia::render('users/IndexUsers', [
            'usuarios' => $usuarios,
        ]);
    }


    public function create()
    {
        $roles = Rol::all();
        return Inertia::render('users/CreateUsers', [
            'roles' => $roles
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'nombre' => ['required', 'regex:/^[a-zA-ZÁÉÍÓÚáéíóúñÑ\s]+$/'],
            'email' => [
                'required',
                'email',
                'regex:/@gmail\.com$/',
                'unique:usuario,email' //  Esta línea es la validación de duplicado (que no se pueda poner un correo ya existente)
            ],
            'contrasena' => [
                'required',
                'min:8',
                'regex:/^(?=.*[a-zA-Z])(?=.*\d)(?=.*[@$!%*?&]).+$/'
            ],
            'id_rol' => ['required', 'exists:rol,id_rol']
        ]);

        Usuario::create([
            'nombre' => $request->nombre,
            'email' => $request->email,
            'contrasena_hash' => Hash::make($request->contrasena),
            'id_rol' => $request->id_rol,
            'estado' => 'Activo',
            'eliminado' => 0
        ]);
        $admin = Auth::user()->nombre;
        $this->registrarAuditoria('Crear usuario', "$admin creó al usuario {$request->nombre} ({$request->email})");
        return redirect()->route('users.index')->with('success', 'Usuario creado correctamente.');
    }

    public function destroy($id)
    {
        $usuario = Usuario::findOrFail($id);

        // Validar si el usuario es un administrador
        $esAdmin = $usuario->rol && strtolower($usuario->rol->nombre) === 'administrador';

        // Contar admins restantes (excluyendo al que se quiere eliminar)
        $adminsRestantes = Usuario::whereHas('rol', function ($query) {
            $query->where('nombre', 'Administrador');
        })->where('id_usuario', '!=', $id)
            ->where('eliminado', 0)
            ->count();

        if ($esAdmin && $adminsRestantes === 0) {
            return back()->withErrors(['error' => 'No puedes eliminar el último administrador del sistema.']);
        }

        $usuario->update(['eliminado' => 1]);
        $admin = Auth::user()->nombre;
        $this->registrarAuditoria('Eliminar usuario', "$admin eliminó al usuario {$usuario->nombre}");

        return redirect()->route('users.index')->with('success', 'Usuario eliminado correctamente.');
    }
    public function deleted()
    {
        $usuarios = Usuario::with('rol')
            ->where('eliminado', 1)
            ->get();

        return Inertia::render('users/DeletedUsers', [
            'usuarios' => $usuarios,
        ]);
    }

    public function restaurar($id)
    {
        Log::info("Restaurando usuario con ID $id");
        $usuario = Usuario::findOrFail($id);

        if ((int) $usuario->eliminado !== 1) {
            return back()->withErrors(['error' => 'Este usuario no está marcado como eliminado.']);
        }
        $admin = Auth::user()->nombre;
        $this->registrarAuditoria('Restaurar usuario', "$admin restauró al usuario {$usuario->nombre}");

        $usuario->update(['eliminado' => 0]);
    }
    public function edit($id)
    {
        $usuario = Usuario::with('rol')->find($id);

        // Si no se encuentra el usuario, redirigir atrás o a listado
        if (!$usuario) {
            return redirect()->route('users.index')->withErrors([
                'usuario' => 'El usuario no existe.',
            ]);
        }

        $roles = Rol::all();

        return Inertia::render('users/EditUsers', [
            'usuario' => $usuario,
            'roles' => $roles,
        ]);
    }


    public function update(Request $request, $id)
    {
        $usuario = Usuario::findOrFail($id);

        $request->validate([
            'nombre' => ['required', 'regex:/^[a-zA-ZÁÉÍÓÚáéíóúñÑ\s]+$/'],
            'email' => [
                'required',
                'email',
                'regex:/@gmail\.com$/',
                'unique:usuario,email,' . $id . ',id_usuario'
            ],
            'id_rol' => ['required', 'exists:rol,id_rol']
        ]);

        $usuario->update([
            'nombre' => $request->nombre,
            'email' => $request->email,
            'id_rol' => $request->id_rol,
        ]);
        $admin = Auth::user()->nombre;
        $this->registrarAuditoria('Actualizar usuario', "$admin actualizó los datos del usuario {$usuario->nombre}");
        return redirect()->route('users.index')->with('success', 'Usuario actualizado correctamente.');
    }

    public function desbloquear($id)
    {
        $usuario = Usuario::findOrFail($id);
        $usuario->update([
            'bloqueado' => false,
            'bloqueado_hasta' => null,
            'intentos_fallidos' => 0,
            'bloqueos_hoy' => 0,
        ]);
        $admin = Auth::user()->nombre;
        $this->registrarAuditoria('Desbloquear usuario', "$admin desbloqueó al usuario {$usuario->nombre}");

        return redirect()->route('users.index')->with('success', 'Usuario desbloqueado correctamente.');
    }
    private function registrarAuditoria(string $accion, string $descripcion): void
    {
        Auditorium::create([
            'id_usuario' => Auth::id(),
            'accion' => $accion,
            'descripcion' => $descripcion,
            'fecha_hora' => now(),
            'eliminado' => 0,
        ]);
    }
}
