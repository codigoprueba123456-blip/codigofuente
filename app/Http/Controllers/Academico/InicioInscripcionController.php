<?php

namespace App\Http\Controllers\Academico;

use App\Http\Controllers\Controller;
use App\Models\Carrera;
use App\Models\GestionAcademica;
use App\Models\Grupo;
use App\Models\Materia;
use Illuminate\Database\Eloquent\Collection;

class InicioInscripcionController extends Controller
{
    /**
     * Resume los periodos registrados y cuenta carreras y materias activas junto con grupos existentes.
     * También identifica la gestión habilitada más reciente para que la vista indique si admite programación.
     */
    public function index()
    {
        $gestiones = GestionAcademica::query()
            ->withCount(['grupos', 'actividades'])
            ->orderByDesc('gestion')
            ->orderByDesc('periodo')
            ->get();
        $gestionHabilitada = GestionAcademica::habilitadas()
            ->orderByDesc('gestion')
            ->orderBy('periodo')
            ->first();

        return view('inicio_inscripcion.index', [
            'gestiones' => $gestiones,
            'gestionHabilitadaId' => $gestionHabilitada?->id_gestionacademica,
            'cantidadCarreras' => Carrera::activas()->count(),
            'cantidadMaterias' => Materia::activas()->count(),
            'cantidadGrupos' => Grupo::count(),
        ]);
    }

    /**
     * Carga una gestión y sus grupos con materia, carrera y cantidad de inscritos.
     * Agrupa los datos por carrera, agrega carreras activas sin grupos, separa activas/inactivas
     * por resolución y consulta las actividades del periodo para construir el resumen de gestión.
     */
    public function gestion(int $gestionId)
    {
        $gestion = GestionAcademica::findOrFail($gestionId);
        $grupos = Grupo::query()
            ->with(['materia.carrera'])
            ->withCount('inscritos')
            ->where('id_gestionacademica', $gestion->id_gestionacademica)
            ->whereHas('materia.carrera')
            ->orderBy('codigo')
            ->get();
        $carreras = $grupos
            ->groupBy(fn (Grupo $grupo): int => (int) $grupo->materia->id_carrera)
            ->map(function (Collection $gruposCarrera): array {
                $carrera = $gruposCarrera->first()->materia->carrera;

                return [
                    'carrera' => $carrera,
                    'resolucion' => $this->etiquetaResolucion($carrera),
                    'materias' => $gruposCarrera
                        ->pluck('materia')
                        ->unique('id_materia')
                        ->sortBy(fn (Materia $materia) => [$materia->nivel ?? 0, $materia->nombre])
                        ->values(),
                    'grupos' => $gruposCarrera,
                    'turnos' => $gruposCarrera->pluck('turno')->filter()->unique()->values(),
                ];
            });
        $carrerasConGrupos = $carreras->pluck('carrera.id_carrera')->all();
        $carrerasSinGrupos = Carrera::activas()
            ->whereNotIn('id_carrera', $carrerasConGrupos)
            ->get()
            ->map(fn (Carrera $carrera): array => [
                'carrera' => $carrera,
                'resolucion' => $this->etiquetaResolucion($carrera),
                'materias' => collect(),
                'grupos' => collect(),
                'turnos' => collect(),
            ]);
        $carreras = $carreras
            ->concat($carrerasSinGrupos)
            ->sortBy(fn (array $item): array => [
                mb_strtolower($item['resolucion']),
                mb_strtolower($item['carrera']->nombre),
            ])
            ->values();
        $carrerasActivasPorResolucion = $carreras
            ->filter(fn (array $item): bool => $item['carrera']->activo)
            ->groupBy('resolucion');
        $carrerasDeshabilitadasPorResolucion = $carreras
            ->filter(fn (array $item): bool => ! $item['carrera']->activo)
            ->groupBy('resolucion');
        $actividades = $gestion->actividades()
            ->orderBy('inicio')
            ->orderBy('nombre')
            ->get();
        $gestionHabilitadaId = GestionAcademica::habilitadas()
            ->orderByDesc('gestion')
            ->orderBy('periodo')
            ->value('id_gestionacademica');

        return view('inicio_inscripcion.gestion', [
            'gestion' => $gestion,
            'carreras' => $carreras,
            'carrerasActivasPorResolucion' => $carrerasActivasPorResolucion,
            'carrerasDeshabilitadasPorResolucion' => $carrerasDeshabilitadasPorResolucion,
            'actividades' => $actividades,
            'puedeProgramarGrupos' => (int) $gestionHabilitadaId === (int) $gestion->id_gestionacademica,
        ]);
    }

    /**
     * Filtra grupos de la carrera dentro de la gestión y los agrupa por paralelo y turno.
     * Calcula cantidades de materias, grupos y turnos y expone si la gestión se puede editar o programar.
     * Si no hay grupos para esa combinación, responde 404 en vez de mostrar una página vacía.
     */
    public function carrera(int $gestionId, int $carreraId)
    {
        $gestion = GestionAcademica::findOrFail($gestionId);
        $carrera = Carrera::findOrFail($carreraId);
        $grupos = $this->gruposDeCarrera($gestion, $carrera);

        abort_if($grupos->isEmpty(), 404);

        $paralelosPorTurno = $grupos
            ->groupBy(fn (Grupo $grupo): string => $this->nombreParalelo($grupo->codigo).'|'.(int) ($grupo->turno ?? 0))
            ->map(function (Collection $gruposParalelo): array {
                $primerGrupo = $gruposParalelo->first();

                return [
                    'nombre' => $this->nombreParalelo($primerGrupo->codigo),
                    'turno' => $primerGrupo->turno,
                    'grupos' => $gruposParalelo
                        ->sortBy(fn (Grupo $grupo): array => [
                            (int) ($grupo->materia->nivel ?? 0),
                            mb_strtolower($grupo->materia->nombre),
                        ])
                        ->values(),
                ];
            })
            ->sortBy(fn (array $paralelo): array => [
                mb_strtolower($paralelo['nombre']),
                (int) ($paralelo['turno'] ?? 0),
            ])
            ->values();
        $gestionHabilitadaId = GestionAcademica::habilitadas()
            ->orderByDesc('gestion')
            ->orderBy('periodo')
            ->value('id_gestionacademica');
        $gestionEsEditable = GestionAcademica::habilitadas()
            ->whereKey($gestion->id_gestionacademica)
            ->exists();

        return view('inicio_inscripcion.carrera', [
            'gestion' => $gestion,
            'carrera' => $carrera,
            'paralelosPorTurno' => $paralelosPorTurno,
            'cantidadMaterias' => $grupos->pluck('id_materia')->unique()->count(),
            'cantidadGrupos' => $grupos->count(),
            'cantidadTurnos' => $grupos->pluck('turno')->filter()->unique()->count(),
            'puedeProgramarGrupos' => (int) $gestionHabilitadaId === (int) $gestion->id_gestionacademica,
            'puedeEditarGrupos' => $gestionEsEditable,
        ]);
    }

    /**
     * Selecciona solo los grupos del paralelo y turno indicados y los agrupa por materia.
     * Ordena materias por nivel y nombre, y marca si la gestión seleccionada permite editar grupos.
     */
    public function paralelo(int $gestionId, int $carreraId, string $paralelo, int $turno)
    {
        $gestion = GestionAcademica::findOrFail($gestionId);
        $carrera = Carrera::findOrFail($carreraId);
        $grupos = $this->gruposDeCarrera($gestion, $carrera)
            ->filter(fn (Grupo $grupo): bool =>
                $this->nombreParalelo($grupo->codigo) === $paralelo
                && (int) ($grupo->turno ?? 0) === $turno
            )
            ->values();

        abort_if($grupos->isEmpty(), 404);

        $materias = $grupos
            ->groupBy('id_materia')
            ->map(fn (Collection $gruposMateria): array => [
                'materia' => $gruposMateria->first()->materia,
                'grupos' => $gruposMateria,
            ])
            ->sortBy(fn (array $item): array => [
                (int) ($item['materia']->nivel ?? 0),
                mb_strtolower($item['materia']->nombre),
            ])
            ->values();
        $puedeEditarGrupos = GestionAcademica::habilitadas()
            ->whereKey($gestion->id_gestionacademica)
            ->exists();

        return view('inicio_inscripcion.paralelo', [
            'gestion' => $gestion,
            'carrera' => $carrera,
            'paralelo' => $paralelo,
            'turno' => $turno,
            'materias' => $materias,
            'puedeEditarGrupos' => $puedeEditarGrupos,
        ]);
    }

    /**
     * Restringe los grupos a la gestión, carrera, paralelo, turno y materia solicitados.
     * Usa el primer resultado para obtener la materia y entrega el conjunto completo a la vista;
     * una combinación sin registros produce 404.
     */
    public function materia(
        int $gestionId,
        int $carreraId,
        string $paralelo,
        int $turno,
        int $materiaId
    ) {
        $gestion = GestionAcademica::findOrFail($gestionId);
        $carrera = Carrera::findOrFail($carreraId);
        $grupos = $this->gruposDeCarrera($gestion, $carrera)
            ->filter(fn (Grupo $grupo): bool =>
                (int) $grupo->id_materia === $materiaId
                && $this->nombreParalelo($grupo->codigo) === $paralelo
                && (int) ($grupo->turno ?? 0) === $turno
            )
            ->values();

        abort_if($grupos->isEmpty(), 404);

        $materia = $grupos->first()->materia;
        $puedeEditarGrupos = GestionAcademica::habilitadas()
            ->whereKey($gestion->id_gestionacademica)
            ->exists();

        return view('inicio_inscripcion.materia', [
            'gestion' => $gestion,
            'carrera' => $carrera,
            'paralelo' => $paralelo,
            'turno' => $turno,
            'materia' => $materia,
            'grupos' => $grupos,
            'puedeEditarGrupos' => $puedeEditarGrupos,
        ]);
    }

    /**
     * Consulta grupos de la gestión cuya materia pertenece a la carrera indicada.
     * Precarga materia y cantidad de inscritos y ordena por código para reutilizar el mismo conjunto
     * en las pantallas de carrera, paralelo y materia.
     */
    private function gruposDeCarrera(GestionAcademica $gestion, Carrera $carrera): Collection
    {
        return Grupo::query()
            ->with('materia')
            ->withCount('inscritos')
            ->where('id_gestionacademica', $gestion->id_gestionacademica)
            ->whereHas('materia', fn ($query) => $query->where('id_carrera', $carrera->id_carrera))
            ->orderBy('codigo')
            ->get();
    }

    /**
     * Convierte las resoluciones guardadas de la carrera en una etiqueta uniforme para agrupar la lista.
     * Si no hay resoluciones válidas, devuelve un texto explícito en lugar de una etiqueta vacía.
     */
    private function etiquetaResolucion(Carrera $carrera): string
    {
        $resoluciones = $carrera->resolucionesMinisteriales();

        return $resoluciones
            ? 'R.M. '.implode(' / ', $resoluciones)
            : 'Sin resolución registrada';
    }

    /**
     * Interpreta el último segmento del código de grupo como paralelo cuando contiene una sola letra.
     * Devuelve esa letra en mayúscula; si el código usa otro formato, conserva el código íntegro.
     */
    private function nombreParalelo(string $codigo): string
    {
        $partes = explode('-', trim($codigo));
        $ultimoSegmento = end($partes);

        // Los grupos se codifican como MATERIA-a, MATERIA-b, etc.; la última letra representa
        // el paralelo visible (A, B...). Si el código no termina en una sola letra, se conserva completo.
        return is_string($ultimoSegmento) && preg_match('/^[a-z]$/i', $ultimoSegmento)
            ? mb_strtoupper($ultimoSegmento)
            : $codigo;
    }
}
