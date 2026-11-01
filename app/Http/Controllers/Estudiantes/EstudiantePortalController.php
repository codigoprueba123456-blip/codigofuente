<?php

namespace App\Http\Controllers\Estudiantes;

use App\Http\Controllers\Controller;
use App\Models\Carrera;
use App\Models\Persona;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class EstudiantePortalController extends Controller
{
    public function estadoInscripcion(Request $request): View
    {
        $persona = $this->personaAutenticada();
        $inscripciones = collect();
        $carreras = collect();
        $gestiones = collect();
        $materias = collect();
        $inscripcionSeleccionada = null;
        $nivel = null;

        if ($persona?->estudiante) {
            $inscripciones = DB::table('inscrito as i')
                ->join('carreras as c', 'c.id_carrera', '=', 'i.id_carrera')
                ->join('gestionacademica as ga', 'ga.id_gestionacademica', '=', 'i.id_gestionacademica')
                ->leftJoin('cursa as cu', function ($join): void {
                    $join->on('cu.id_persona', '=', 'i.id_persona')
                        ->on('cu.id_carrera', '=', 'i.id_carrera')
                        ->on('cu.id_gestionacademica', '=', 'i.id_gestionacademica');
                })
                ->where('i.id_persona', $persona->id_persona)
                ->orderByDesc('ga.gestion')
                ->orderByDesc('ga.periodo')
                ->orderByDesc('i.id_gestionacademica')
                ->distinct()
                ->get([
                    'i.id_carrera',
                    'c.nombre as carrera',
                    'i.id_gestionacademica',
                    'ga.gestion',
                    'ga.periodo',
                    'cu.nivelinicio',
                ]);

            $carrerasPorId = Carrera::whereIn('id_carrera', $inscripciones->pluck('id_carrera')->unique())
                ->get()
                ->keyBy('id_carrera');
            $carreras = $inscripciones->unique('id_carrera')
                ->map(fn (object $inscripcion): ?Carrera => $carrerasPorId->get($inscripcion->id_carrera))
                ->filter()
                ->values();
            $idCarrera = (int) $request->query('id_carrera', $carreras->first()->id_carrera ?? 0);
            if (! $carreras->contains(fn ($carrera): bool => (int) $carrera->id_carrera === $idCarrera)) {
                $idCarrera = (int) ($carreras->first()->id_carrera ?? 0);
            }

            $gestiones = $inscripciones
                ->where('id_carrera', $idCarrera)
                ->values();
            $idGestion = (int) $request->query('id_gestionacademica', $gestiones->first()->id_gestionacademica ?? 0);
            if (! $gestiones->contains(fn ($gestion): bool => (int) $gestion->id_gestionacademica === $idGestion)) {
                $idGestion = (int) ($gestiones->first()->id_gestionacademica ?? 0);
            }

            if ($idCarrera && $idGestion) {
                $inscripcionSeleccionada = DB::table('inscrito as i')
                    ->join('gestionacademica as ga', 'ga.id_gestionacademica', '=', 'i.id_gestionacademica')
                    ->where('i.id_persona', $persona->id_persona)
                    ->where('i.id_carrera', $idCarrera)
                    ->where('i.id_gestionacademica', $idGestion)
                    ->first([
                        'i.id_inscrito',
                        'i.id_carrera',
                        'i.id_gestionacademica',
                        'ga.gestion',
                        'ga.periodo',
                    ]);

                if ($inscripcionSeleccionada) {
                    $materias = DB::table('inscrito_materia as im')
                        ->join('materia as m', 'm.id_materia', '=', 'im.id_materia')
                        ->leftJoin('grupo as g', 'g.id_grupo', '=', 'im.id_grupo')
                        ->leftJoin('empleados as e', 'e.id_persona', '=', 'g.id_persona')
                        ->leftJoin('personas as docente', 'docente.id_persona', '=', 'e.id_persona')
                        ->where('im.id_inscrito', $inscripcionSeleccionada->id_inscrito)
                        ->orderBy('m.nivel')
                        ->orderBy('m.numero')
                        ->orderBy('m.nombre')
                        ->get([
                            'm.nivel',
                            'm.numero',
                            'm.codigo',
                            'm.nombre',
                            'g.codigo as paralelo',
                            'g.turno',
                            'docente.nombre as docente_nombre',
                            'docente.primerapellido as docente_primerapellido',
                            'docente.segundoapellido as docente_segundoapellido',
                        ]);

                    $nivel = DB::table('cursa')
                        ->where('id_persona', $persona->id_persona)
                        ->where('id_carrera', $idCarrera)
                        ->where('id_gestionacademica', $idGestion)
                        ->value('nivelinicio');

                    if ($nivel === null) {
                        $nivel = DB::table('inscrito as i')
                            ->join('gestionacademica as ga', 'ga.id_gestionacademica', '=', 'i.id_gestionacademica')
                            ->where('i.id_persona', $persona->id_persona)
                            ->where('i.id_carrera', $idCarrera)
                            ->where(function ($query) use ($inscripcionSeleccionada): void {
                                $query->where('ga.gestion', '<', $inscripcionSeleccionada->gestion)
                                    ->orWhere(function ($query) use ($inscripcionSeleccionada): void {
                                        $query->where('ga.gestion', $inscripcionSeleccionada->gestion)
                                            ->where('ga.periodo', '<', $inscripcionSeleccionada->periodo);
                                    })
                                    ->orWhere(function ($query) use ($inscripcionSeleccionada): void {
                                        $query->where('ga.gestion', $inscripcionSeleccionada->gestion)
                                            ->where('ga.periodo', $inscripcionSeleccionada->periodo)
                                            ->where('i.id_gestionacademica', '<', $inscripcionSeleccionada->id_gestionacademica);
                                    });
                            })
                            ->distinct()
                            ->count('i.id_gestionacademica') + 1;
                    }
                }
            }
        }

        return view('estudiantes.estado_inscripcion.index', compact(
            'persona',
            'inscripciones',
            'carreras',
            'gestiones',
            'inscripcionSeleccionada',
            'materias',
            'nivel'
        ));
    }

    public function historialEconomico(Request $request): View
    {
        $persona = $this->personaAutenticada();
        $carreras = collect();
        $pagos = collect();
        $carreraSeleccionada = null;

        if ($persona?->estudiante) {
            $carreras = Carrera::whereIn(
                'id_carrera',
                DB::table('inscrito')->where('id_persona', $persona->id_persona)->select('id_carrera')
            )->orderBy('nombre')->get();

            $idCarrera = (int) $request->query('id_carrera', $request->query('carreras', 0));
            $carreraSeleccionada = $carreras->contains(
                fn ($carrera): bool => (int) $carrera->id_carrera === $idCarrera
            )
                ? $idCarrera
                : (int) ($carreras->first()->id_carrera ?? 0);

            $detalles = DB::table('detalle')
                ->select('id_pago')
                ->selectRaw('SUM(monto) as monto')
                ->groupBy('id_pago');
            $pagosPorInscripcion = DB::table('pago as pago')
                ->leftJoinSub($detalles, 'detalle', 'detalle.id_pago', '=', 'pago.id_pago')
                ->select('pago.id_inscrito', 'pago.id_postulante')
                ->selectRaw('SUM(COALESCE(detalle.monto, pago.monto, 0)) as monto')
                ->groupBy('pago.id_inscrito', 'pago.id_postulante');

            $pagos = DB::table('comprobante as cb')
                ->join('gestionacademica as ga', 'ga.id_gestionacademica', '=', 'cb.id_gestionacademica')
                ->join('carreras as c', 'c.id_carrera', '=', 'cb.id_carrera')
                ->leftJoin('inscrito as i', 'i.id_inscrito', '=', 'cb.id_inscrito')
                ->leftJoinSub($pagosPorInscripcion, 'pa', function ($join): void {
                    $join->on('pa.id_inscrito', '=', 'cb.id_inscrito')
                        ->orOn('pa.id_postulante', '=', 'cb.id_postulante');
                })
                ->where('cb.id_estudiante', $persona->id_persona)
                ->where('cb.id_carrera', $carreraSeleccionada)
                ->orderByDesc('ga.gestion')
                ->orderByDesc('ga.periodo')
                ->orderByDesc('cb.id_comprobante')
                ->get([
                    'cb.id_comprobante as id_pago',
                    DB::raw('COALESCE(pa.monto, 0) as monto'),
                    'cb.fecha',
                    'cb.id_gestionacademica',
                    'ga.gestion',
                    'ga.periodo',
                    'c.nombre as carrera',
                    'i.codigo',
                    'cb.deposito as comprobante',
                ]);
        }

        return view('estudiantes.historial_economico.index', compact(
            'persona',
            'carreras',
            'pagos',
            'carreraSeleccionada'
        ));
    }

    public function fileEstudiante(): View
    {
        $persona = $this->personaAutenticada();

        return view('estudiantes.file_estudiante.index', compact('persona'));
    }

    public function actualizarFile(Request $request): RedirectResponse
    {
        $persona = $this->personaAutenticada();
        abort_unless($persona?->estudiante, 403, 'La cuenta no está vinculada a una ficha de estudiante.');

        $datos = $request->validate([
            'direccion' => ['required', 'string', 'max:255'],
            'telefono' => ['nullable', 'string', 'max:20'],
            'celular' => ['nullable', 'string', 'max:20'],
            'email' => ['nullable', 'email', 'max:255'],
            'nombrecontacto' => ['nullable', 'string', 'max:255'],
            'celularcontacto' => ['nullable', 'string', 'max:20'],
            'parentescocontacto' => ['nullable', 'string', 'max:255'],
            'nombrecolegio' => ['nullable', 'string', 'max:255'],
            'caractercolegio' => ['nullable', 'in:Fiscal,Particular,Convenio'],
            'egresocolegio' => ['nullable', 'integer', 'between:1900,2100'],
        ]);

        DB::transaction(function () use ($persona, $datos): void {
            $persona->update([
                'direccion' => $datos['direccion'],
                'telefono' => $datos['telefono'] ?? null,
                'celular' => $datos['celular'] ?? null,
                'email' => $datos['email'] ?? null,
            ]);

            $persona->estudiante->update([
                'nombrecontacto' => $datos['nombrecontacto'] ?? null,
                'celularcontacto' => $datos['celularcontacto'] ?? null,
                'parentescocontacto' => $datos['parentescocontacto'] ?? null,
                'nombrecolegio' => $datos['nombrecolegio'] ?? null,
                'caractercolegio' => $datos['caractercolegio'] ?? null,
                'egresocolegio' => $datos['egresocolegio'] ?? null,
            ]);
        });

        return redirect()
            ->route('estudiantes.file_estudiante')
            ->with('success', 'Tus datos se actualizaron correctamente.');
    }

    private function personaAutenticada(): ?Persona
    {
        $idPersona = request()->user()?->id_persona;
        if (! $idPersona) {
            return null;
        }

        return Persona::query()
            ->with('estudiante')
            ->find($idPersona);
    }
}
