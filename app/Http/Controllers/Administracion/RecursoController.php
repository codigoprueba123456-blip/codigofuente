<?php

namespace App\Http\Controllers\Administracion;

use App\Http\Controllers\Controller;
use App\Models\Recurso;
use App\Support\MenuResourceSync;
use Illuminate\Http\Request;

class RecursoController extends Controller
{
    /**
     * Sincroniza los enlaces actuales del menú con el catálogo y lista los recursos resultantes.
     */
    public function index()
    {
        MenuResourceSync::sync();
        $recursos = Recurso::orderBy('nombre')->get();

        return view('admin.recursos.index', compact('recursos'));
    }

    /**
     * Sincroniza el menú antes de mostrar la vista para que el catálogo de recursos esté al día.
     */
    public function create()
    {
        MenuResourceSync::sync();

        return view('admin.recursos.create');
    }

    /**
     * Crea o actualiza el recurso identificado por su URL normalizada.
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
