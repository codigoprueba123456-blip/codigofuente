<?php

namespace App\Http\Controllers\Academico;

use App\Http\Controllers\Controller;
use App\Models\Carrera;
use App\Models\GestionAcademica;
use App\Models\TarifaMatricula;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class TarifaMatriculaController extends Controller
{
    /**
     * Resuelve la gestión, carga carreras activas y tarifas guardadas con su carrera relacionada.
     * Envía estos datos a la pantalla donde se agregan, editan o eliminan montos.
     */
    public function index(int $id)
    {
        $gestion = GestionAcademica::findOrFail($id);
        $carreras = Carrera::query()->where('activo', true)->orderBy('nombre')->get();
        $tarifas = TarifaMatricula::query()
            ->with('carrera')
            ->where('id_gestionacademica', $gestion->id_gestionacademica)
            ->orderBy('id_carrera')
            ->orderBy('nivel')
            ->get();

        return view('gestiones.tarifas_matricula.index', compact('gestion', 'carreras', 'tarifas'));
    }

    /**
     * Valida gestión, carrera, nivel y monto, y comprueba que el nivel corresponda al régimen.
     * Inserta o reemplaza la tarifa de esa combinación única; el nivel cero representa todos los niveles.
     */
    public function store(Request $request, int $id)
    {
        $gestion = GestionAcademica::findOrFail($id);
        $validated = $request->validate($this->rules());
        $this->validarNivel($validated['id_carrera'], (int) $validated['nivel']);

        TarifaMatricula::updateOrCreate(
            [
                'id_gestionacademica' => $gestion->id_gestionacademica,
                'id_carrera' => $validated['id_carrera'],
                'nivel' => $validated['nivel'],
            ],
            ['monto' => $validated['monto']]
        );

        return redirect()->route('admin.gestionacademica.tarifas.index', $gestion->id_gestionacademica)
            ->with('success', 'Tarifa de matrícula guardada correctamente.');
    }

    /**
     * Busca una tarifa que pertenezca a la gestión solicitada y valida un monto no negativo de hasta
     * dos decimales; solo actualiza el importe, manteniendo carrera y nivel originales.
     */
    public function update(Request $request, int $id, int $tarifaId)
    {
        $gestion = GestionAcademica::findOrFail($id);
        $tarifa = TarifaMatricula::query()
            ->where('id_gestionacademica', $gestion->id_gestionacademica)
            ->findOrFail($tarifaId);
        $validated = $request->validate([
            'monto' => ['required', 'numeric', 'min:0', 'max:9999999999.99', 'regex:/^\d+(\.\d{1,2})?$/'],
        ]);
        $tarifa->update(['monto' => $validated['monto']]);

        return redirect()->route('admin.gestionacademica.tarifas.index', $gestion->id_gestionacademica)
            ->with('success', 'Tarifa de matrícula actualizada correctamente.');
    }

    /**
     * Elimina únicamente la tarifa cuyo ID pertenece a la gestión de la URL.
     * Los siguientes cálculos usarán otra tarifa coincidente o el monto de respaldo vigente.
     */
    public function destroy(int $id, int $tarifaId)
    {
        $gestion = GestionAcademica::findOrFail($id);
        TarifaMatricula::query()
            ->where('id_gestionacademica', $gestion->id_gestionacademica)
            ->findOrFail($tarifaId)
            ->delete();

        return redirect()->route('admin.gestionacademica.tarifas.index', $gestion->id_gestionacademica)
            ->with('success', 'Tarifa eliminada; se aplicará el siguiente monto disponible.');
    }

    /**
     * Define las reglas para carrera existente, nivel entre cero y seis y monto con máximo de dos decimales.
     * El significado del nivel y su compatibilidad con el régimen se verifican por separado en validarNivel().
     */
    private function rules(): array
    {
        return [
            'id_carrera' => ['required', 'integer', 'exists:carreras,id_carrera'],
            'nivel' => ['required', 'integer', 'between:0,6'],
            'monto' => ['required', 'numeric', 'min:0', 'max:9999999999.99', 'regex:/^\d+(\.\d{1,2})?$/'],
        ];
    }

    /**
     * Acepta cero como tarifa general; para niveles específicos compara contra los niveles del régimen.
     * Si carrera o nivel no son válidos, agrega el error al campo nivel y evita guardar la tarifa.
     */
    private function validarNivel(int|string $idCarrera, int $nivel): void
    {
        if ($nivel === 0) {
            return;
        }

        $carrera = Carrera::findOrFail($idCarrera);
        if (! in_array($nivel, Carrera::nivelesPorRegimen($carrera->regimen), true)) {
            throw ValidationException::withMessages([
                'nivel' => 'El nivel seleccionado no corresponde al régimen académico de la carrera.',
            ]);
        }
    }
}
