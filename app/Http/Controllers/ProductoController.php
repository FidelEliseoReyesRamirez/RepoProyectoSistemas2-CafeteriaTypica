<?php

namespace App\Http\Controllers;

use App\Models\Producto;
use App\Models\Categorium;
use App\Models\Auditorium;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Auth;

class ProductoController extends Controller
{
    public function index()
    {
        $productos = Producto::with('categorium')
            ->where('eliminado', 0)
            ->get();

        return Inertia::render('products/IndexProductos', [
            'productos' => $productos,
        ]);
    }

    public function create()
    {
        $categorias = Categorium::where('eliminado', 0)->get();
        return Inertia::render('products/CreateProducto', [
            'categorias' => $categorias,
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'nombre' => ['required', 'string'],
            'precio' => ['required', 'numeric'],
            'id_categoria' => ['nullable', 'exists:categoria,id_categoria'],
        ]);

        $producto = Producto::create($request->all());

        $admin = Auth::user()->nombre;
        $this->registrarAuditoria('Crear producto', "$admin creó el producto {$producto->nombre}");

        return redirect()->route('productos.index')->with('success', 'Producto creado correctamente.');
    }

    public function edit(Producto $producto)
    {
        $categorias = Categorium::where('eliminado', 0)->get();
        return Inertia::render('products/EditProducto', [
            'producto' => $producto,
            'categorias' => $categorias,
        ]);
    }

    public function update(Request $request, Producto $producto)
    {
        $request->validate([
            'nombre' => ['required', 'string'],
            'precio' => ['required', 'numeric'],
            'id_categoria' => ['nullable', 'exists:categoria,id_categoria'],
        ]);

        $producto->update($request->all());

        $admin = Auth::user()->nombre;
        $this->registrarAuditoria('Actualizar producto', "$admin actualizó el producto {$producto->nombre}");

        return redirect()->route('productos.index')->with('success', 'Producto actualizado correctamente.');
    }

    public function destroy(Producto $producto)
    {
        $producto->update(['eliminado' => 1]);

        $admin = Auth::user()->nombre;
        $this->registrarAuditoria('Eliminar producto', "$admin eliminó el producto {$producto->nombre}");

        return redirect()->route('productos.index')->with('success', 'Producto eliminado correctamente.');
    }

    public function toggleDisponibilidad(Producto $producto)
    {
        $producto->update([
            'disponibilidad' => !$producto->disponibilidad
        ]);

        $estado = $producto->disponibilidad ? 'Disponible' : 'Agotado';
        $admin = Auth::user()->nombre;
        $this->registrarAuditoria('Cambiar disponibilidad de producto', "$admin cambió disponibilidad de {$producto->nombre} a $estado");

        return back();
    }

    public function deleted()
    {
        $productos = Producto::with('categorium')
            ->where('eliminado', 1)
            ->get();

        return Inertia::render('products/DeletedProductos', [
            'productos' => $productos,
        ]);
    }

    public function restore(Producto $producto)
    {
        $producto->update(['eliminado' => 0]);

        $admin = Auth::user()->nombre;
        $this->registrarAuditoria('Restaurar producto', "$admin restauró el producto {$producto->nombre}");

        return redirect()->route('productos.deleted')->with('success', 'Producto restaurado correctamente.');
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
    public function actualizarCantidad(Request $request, $id)
    {
        $request->validate([
            'cantidad' => 'required|integer|min:0'
        ]);

        $producto = Producto::findOrFail($id);
        $producto->cantidad_disponible = $request->cantidad;
        $producto->save();

        return back();
    }
}
