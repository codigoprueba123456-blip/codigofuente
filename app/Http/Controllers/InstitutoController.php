<?php

namespace App\Http\Controllers;

use App\Models\Instituto;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class InstitutoController extends Controller
{
    public function index()
    {
        $instituto = Instituto::first() ?? new Instituto;

        return view('admin.instituto.index', compact('instituto'));
    }

    public function update(Request $request)
    {
        $validated = $request->validate([
            'nombreinstituto' => ['required', 'string', 'max:255'],
            'nombreregulador' => ['required', 'string', 'max:255'],
            'ubicacion' => ['required', 'string', 'max:255'],
            'caracter' => ['required', 'in:FISCAL,PRIVADO'],
            'nombreimpuestos' => ['nullable', 'string', 'max:255'],
            'nit' => ['nullable', 'string', 'max:255'],
            'actividadeconomica' => ['nullable', 'string', 'max:255'],
            'preciocredito' => ['required', 'integer', 'min:0'],
            'resolucionministerial' => ['required', 'string', 'max:255'],
            'abreviatura' => ['required', 'string', 'max:255'],
            'token' => ['nullable', 'string', 'max:255'],
            'logo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'eliminar_logo' => ['sometimes', 'boolean'],
        ]);

        $instituto = Instituto::first() ?? new Instituto(['id_instituto' => 1]);
        $logoAnterior = $instituto->logo;
        if ($request->hasFile('logo')) {
            $validated['logo'] = $request->file('logo')->store('instituto', 'public');
        } elseif ($request->boolean('eliminar_logo')) {
            $validated['logo'] = null;
        }
        $instituto->fill($validated);
        $instituto->save();

        if ($logoAnterior && ($request->hasFile('logo') || $request->boolean('eliminar_logo'))) {
            Storage::disk('public')->delete($logoAnterior);
        }

        return back()->with('success', 'Configuración actualizada correctamente');
    }
}
