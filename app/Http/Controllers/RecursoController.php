<?php

namespace App\Http\Controllers;

use App\Models\Recurso;
use App\Support\MenuResourceSync;
use Illuminate\Http\Request;

class RecursoController extends Controller
{
    public function index()
    {
        MenuResourceSync::sync();
        $recursos = Recurso::orderBy('nombre')->get();

        return view('admin.recursos.index', compact('recursos'));
    }

    public function create()
    {
        MenuResourceSync::sync();

        return view('admin.recursos.create');
    }

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
