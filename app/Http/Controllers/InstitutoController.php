<?php

namespace App\Http\Controllers;

use App\Models\Instituto;
use App\Models\Empleado;
use App\Support\CareerCreditageService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class InstitutoController extends Controller
{
    public function index()
    {
        $instituto = Instituto::first() ?? new Instituto;
        $empleados = Empleado::with('persona')
            ->whereHas('persona')
            ->get()
            ->sortBy(fn (Empleado $empleado): string => trim(implode(' ', array_filter([
                $empleado->persona->nombre,
                $empleado->persona->primerapellido,
                $empleado->persona->segundoapellido,
            ]))))
            ->values();

        return view('admin.instituto.index', compact('instituto', 'empleados'));
    }

    public function update(Request $request, CareerCreditageService $creditage)
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
            'id_rector' => ['nullable', 'integer', 'exists:empleados,id_persona'],
            'id_directoracademico' => ['nullable', 'integer', 'exists:empleados,id_persona'],
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
        DB::transaction(function () use ($instituto, $creditage): void {
            $instituto->save();
            $creditage->syncInstitute($instituto);
        });

        if ($logoAnterior && ($request->hasFile('logo') || $request->boolean('eliminar_logo'))) {
            Storage::disk('public')->delete($logoAnterior);
        }

        return back()->with('success', 'Configuración actualizada correctamente');
    }
}
