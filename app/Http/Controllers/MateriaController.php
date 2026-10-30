<?php

namespace App\Http\Controllers;

use App\Models\Carrera;
use App\Models\Materia;
use App\Support\PlanEstudiosImporter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use RuntimeException;

class MateriaController extends Controller
{
    public function index()
    {
        $query = Materia::with(['carrera', 'prerequisitos'])->orderBy('nombre');
        if (request()->filled('id_carrera')) {
            $query->where('id_carrera', request('id_carrera'));
        }
        $materias = $query->get();

        return view('planes.materias.index', compact('materias'));
    }

    public function create()
    {
        $carreras = Carrera::orderBy('nombre')->get();
        $materiasDisponibles = Materia::orderBy('nombre')->get();

        return view('planes.materias.create', compact('carreras', 'materiasDisponibles'));
    }

    public function importForm($id)
    {
        $carrera = Carrera::findOrFail($id);

        return view('planes.materias.import', compact('carrera'));
    }

    public function import(Request $request, $id, PlanEstudiosImporter $importer)
    {
        $carrera = Carrera::findOrFail($id);
        $validated = $request->validate([
            'archivo' => ['required', 'file', 'mimes:xlsx,xls,csv,pdf', 'max:10240'],
        ]);

        try {
            $rows = $importer->rows($validated['archivo']);
            $imported = DB::transaction(function () use ($rows, $carrera) {
                $count = 0;

                foreach ($rows as $index => $row) {
                    $validator = Validator::make($row, [
                        'codigo' => ['nullable', 'string', 'max:255'],
                        'nombre' => ['required', 'string', 'max:255'],
                        'creditajemateria' => ['nullable', 'integer', 'min:0'],
                        'horas' => ['nullable', 'integer', 'min:0'],
                        'nivel' => ['nullable', 'integer', 'min:0'],
                        'numero' => ['nullable', 'integer', 'min:0'],
                    ]);

                    if ($validator->fails()) {
                        throw ValidationException::withMessages([
                            'archivo' => 'Error en la fila ' . ($index + 2) . ': ' . $validator->errors()->first(),
                        ]);
                    }

                    $data = $validator->validated();
                    $data['id_carrera'] = $carrera->id_carrera;
                    $data['curricular'] = true;

                    $query = Materia::where('id_carrera', $carrera->id_carrera);
                    $existing = !empty($data['codigo'])
                        ? $query->where('codigo', $data['codigo'])->first()
                        : $query->where('nombre', $data['nombre'])->first();

                    if ($existing) {
                        $existing->update($data);
                    } else {
                        Materia::create($data);
                    }
                    $count++;
                }

                return $count;
            });
        } catch (RuntimeException $exception) {
            return back()->withInput()->withErrors(['archivo' => $exception->getMessage()]);
        }

        return redirect()->route('planes.materias.index', ['id_carrera' => $carrera->id_carrera])
            ->with('success', $imported . ' materias cargadas correctamente para ' . $carrera->nombre . '.');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'codigo' => ['nullable', 'string', 'max:255'],
            'nombre' => ['required', 'string', 'max:255'],
            'creditajemateria' => ['nullable', 'integer', 'min:0'],
            'curricular' => ['nullable', 'boolean'],
            'horas' => ['nullable', 'integer', 'min:0'],
            'nivel' => ['nullable', 'integer', 'min:0'],
            'numero' => ['nullable', 'integer', 'min:0'],
            'id_carrera' => ['required', 'exists:carreras,id_carrera'],
            'prerequisitos' => ['nullable', 'array'],
            'prerequisitos.*' => ['exists:materia,id_materia'],
        ]);

        $materia = Materia::create([
            'codigo' => $validated['codigo'] ?? null,
            'nombre' => $validated['nombre'],
            'creditajemateria' => $validated['creditajemateria'] ?? null,
            'curricular' => $request->has('curricular') ? true : false,
            'horas' => $validated['horas'] ?? null,
            'nivel' => $validated['nivel'] ?? null,
            'numero' => $validated['numero'] ?? null,
            'id_carrera' => $validated['id_carrera'],
        ]);

        if (!empty($validated['prerequisitos'])) {
            $materia->prerequisitos()->sync($validated['prerequisitos']);
        }

        return redirect()->route('planes.materias.index')->with('success', 'Materia creada correctamente.');
    }

    public function edit($id)
    {
        $materia = Materia::with('prerequisitos')->findOrFail($id);
        $carreras = Carrera::orderBy('nombre')->get();
        $materiasDisponibles = Materia::whereKeyNot($id)->orderBy('nombre')->get();

        return view('planes.materias.edit', compact('materia', 'carreras', 'materiasDisponibles'));
    }

    public function update(Request $request, $id)
    {
        $materia = Materia::findOrFail($id);

        $validated = $request->validate([
            'codigo' => ['nullable', 'string', 'max:255'],
            'nombre' => ['required', 'string', 'max:255'],
            'creditajemateria' => ['nullable', 'integer', 'min:0'],
            'curricular' => ['nullable', 'boolean'],
            'horas' => ['nullable', 'integer', 'min:0'],
            'nivel' => ['nullable', 'integer', 'min:0'],
            'numero' => ['nullable', 'integer', 'min:0'],
            'id_carrera' => ['required', 'exists:carreras,id_carrera'],
            'prerequisitos' => ['nullable', 'array'],
            'prerequisitos.*' => ['exists:materia,id_materia'],
        ]);

        $materia->update([
            'codigo' => $validated['codigo'] ?? null,
            'nombre' => $validated['nombre'],
            'creditajemateria' => $validated['creditajemateria'] ?? null,
            'curricular' => $request->has('curricular') ? true : false,
            'horas' => $validated['horas'] ?? null,
            'nivel' => $validated['nivel'] ?? null,
            'numero' => $validated['numero'] ?? null,
            'id_carrera' => $validated['id_carrera'],
        ]);

        $materia->prerequisitos()->sync($validated['prerequisitos'] ?? []);

        return redirect()->route('planes.materias.index')->with('success', 'Materia actualizada correctamente.');
    }

    public function destroy($id)
    {
        $materia = Materia::findOrFail($id);
        $materia->delete();

        return redirect()->route('planes.materias.index')->with('success', 'Materia eliminada correctamente.');
    }
}
