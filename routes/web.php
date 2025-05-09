<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Redirect;
use Inertia\Inertia;

Route::get('/', function () {
    return Redirect::route('login');
})->name('home');

Route::get('dashboard', function () {
    return Inertia::render('Dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

require __DIR__ . '/settings.php';
require __DIR__ . '/auth.php';

use Illuminate\Support\Facades\DB;

Route::get('/rol/{id}', function ($id) {
    $rol = DB::table('rol')
        ->where('id_rol', $id)
        ->select('nombre')
        ->first();

    return response()->json($rol);
});


//USUARIOS
use App\Http\Controllers\UsuarioController;

Route::middleware(['auth', 'verified', 'is_admin'])->group(function () {
    Route::get('/users', [UsuarioController::class, 'index'])->name('users.index');
    Route::get('/users/create', [UsuarioController::class, 'create'])->name('users.create');
    Route::post('/users', [UsuarioController::class, 'store'])->name('users.store');
    Route::delete('/users/{id}', [UsuarioController::class, 'destroy'])->name('users.destroy');
    Route::get('/users/deleted', [UsuarioController::class, 'deleted'])->name('users.deleted');
    Route::put('/users/{id}/restore', [UsuarioController::class, 'restaurar'])->name('users.restore');
    Route::get('/users/{id}/edit', [UsuarioController::class, 'edit'])->name('users.edit');
    Route::put('/users/{id}', [UsuarioController::class, 'update'])->name('users.update');
    Route::put('/users/{id}/unblock', [UsuarioController::class, 'desbloquear'])->name('users.unblock');
});

//PRODUCTOS
use App\Http\Controllers\ProductoController;

Route::middleware(['auth', 'verified', 'can_manage_productos'])->group(function () {
    Route::get('/productos', [ProductoController::class, 'index'])->name('productos.index');
    Route::get('/productos/create', [ProductoController::class, 'create'])->name('productos.create');
    Route::post('/productos', [ProductoController::class, 'store'])->name('productos.store');
    Route::get('/productos/{producto}/edit', [ProductoController::class, 'edit'])->name('productos.edit');
    Route::put('/productos/{producto}', [ProductoController::class, 'update'])->name('productos.update');
    Route::delete('/productos/{producto}', [ProductoController::class, 'destroy'])->name('productos.destroy');
    Route::put('/productos/{producto}/toggle-disponibilidad', [ProductoController::class, 'toggleDisponibilidad'])->name('productos.toggle-disponibilidad');
    Route::put('/productos/{producto}/restore', [ProductoController::class, 'restore'])->name('productos.restore');
    Route::get('/productos/deleted', [ProductoController::class, 'deleted'])->name('productos.deleted');
});


//PEDIDOS
use App\Http\Controllers\PedidoController;

Route::middleware(['auth', 'is_mesero_or_admin'])->group(function () {
    Route::get('/order', [PedidoController::class, 'crear'])->name('order.index');
    Route::post('/order', [PedidoController::class, 'guardar'])->name('order.store');
});
