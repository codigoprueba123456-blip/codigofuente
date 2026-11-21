<?php

namespace App\Http\Controllers\Administracion;

use App\Http\Controllers\Controller;
use App\Models\Recurso;
use App\Support\MenuResourceSync;
use Illuminate\Http\Request;

class RecursoController extends Controller
{
    /**
     * Actualiza primero el catálogo desde los enlaces del menú y luego lista los recursos ordenados.
     * Esta sincronización permite que las pantallas de roles vean las rutas vigentes de la aplicación.
     */
    public function index()
    {
        MenuResourceSync::sync();
        $recursos = Recurso::orderBy('nombre')->get();

        return view('admin.recursos.index', compact('recursos'));
    }

    /**
     * Sincroniza el menú antes de mostrar el formulario de recurso.
     * La vista de alta puede así seleccionar o consultar rutas que ya forman parte de la navegación.
     */
    public function create()
    {
        MenuResourceSync::sync();

        return view('admin.recursos.create');
    }

    /**
     * Valida el nombre y patrón URL, elimina barras externas y guarda el recurso por URL única.
     * Si la ruta ya existe, actualiza su nombre en vez de crear un duplicado.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'nombre' => ['required', 'string', 'max:255'],
            'urlpattern' => ['required', 'string', 'max:255'],
        ]);

        Recurso::updateOrCreate(
            ['urlpattern' => trim($validated['urlpattern'], '/')],
            ['nombre' => $validated['nombre']]
        );

        return redirect()->route('admin.recursos.index')
            ->with('success', 'Recurso creado correctamente.');
    }
}
