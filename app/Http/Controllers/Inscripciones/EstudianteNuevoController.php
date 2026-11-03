<?php

namespace App\Http\Controllers\Inscripciones;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use App\Models\GestionAcademica;
use App\Models\Carrera;

class EstudianteNuevoController extends Controller
{
    /**
     * Muestra la vista del formulario para registrar estudiante nuevo
     */
    public function index()
    {
        // Cargamos las gestiones y carreras para los selectores desplegables
        $gestiones = GestionAcademica::where('vigente', 1)->orderBy('id_gestionacademica', 'desc')->get();
        if ($gestiones->isEmpty()) {
            $gestiones = GestionAcademica::orderBy('id_gestionacademica', 'desc')->get();
        }

        $carreras = Carrera::activas()->orderBy('nombre', 'asc')->get();

        return view('Inscripciones.estudiante_nuevo.index', compact('gestiones', 'carreras'));
    }

    public function buscar(Request $request)
    {
        $ci = preg_replace('/[^A-Za-z0-9-]/', '', (string) $request->query('ci', ''));
        if ($ci === '') {
            return response()->json(['estudiante' => null]);
        }

        $persona = DB::table('personas as p')
            ->join('estudiante as e', 'e.id_persona', '=', 'p.id_persona')
            ->where('p.ci', $ci)
            ->first([
                'p.id_persona',
                'p.nombre',
                'p.primerapellido',
                'p.segundoapellido',
                'p.ci',
                'p.lugarexpedicion',
                'p.fechanacimiento',
                'p.lugarnacimiento',
                'p.nacionalidad',
                'p.sexo',
                'p.direccion',
                'p.telefono',
                'p.celular',
                'p.email',
                'e.nombrecontacto',
                'e.celularcontacto',
                'e.parentescocontacto',
                'e.nombrecolegio',
                'e.caractercolegio',
                'e.egresocolegio',
            ]);

        return response()->json(['estudiante' => $persona]);
    }

    /**
     * Guarda el registro en la base de datos
     * Inserta en tabla personas y luego en tabla estudiante
     */
    public function store(Request $request)
    {
        $ci = preg_replace('/[^A-Za-z0-9-]/', '', (string) $request->input('ci', ''));
        $request->merge(['ci' => $ci]);
        $personaExistente = $ci !== '' ? DB::table('personas')->where('ci', $ci)->first() : null;
        $estudianteExistente = $personaExistente && DB::table('estudiante')
            ->where('id_persona', $personaExistente->id_persona)
            ->exists();

        if ($personaExistente && ! $estudianteExistente) {
            return back()->withInput()->withErrors([
                'ci' => 'Este CI ya pertenece a otra persona del sistema y no está vinculada a una ficha de estudiante.',
            ]);
        }

        $rules = [
            'fecha' => 'required|date',
            'becado' => 'nullable|boolean',
            'deposito' => 'required|string|max:255',
            'id_gestionacademica' => 'required|exists:gestionacademica,id_gestionacademica',
            'traspaso_convalidacion' => 'nullable|boolean',
            'id_carrera' => [
                'required',
                Rule::exists('carreras', 'id_carrera')->where('activo', true),
            ],
            'nivelinicio' => 'required|integer|between:1,3',
            'path_bth' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:350',
        ];

        if (! $estudianteExistente) {
            $rules = [
                ...$rules,
                'nombre' => 'required|string|max:255',
                'primerapellido' => 'required|string|max:255',
                'segundoapellido' => 'nullable|string|max:255',
                'ci' => 'required|string|max:30|unique:personas,ci',
                'lugarexpedicion' => 'nullable|string|max:20',
                'fechanacimiento' => 'required|date',
                'lugarnacimiento' => 'required|string|max:255',
                'nacionalidad' => 'required|string|max:255',
                'sexo' => 'required|in:M,F',
                'direccion' => 'required|string|max:255',
                'telefono' => 'nullable|string|max:20',
                'celular' => 'nullable|string|max:20',
                'email' => 'required|email|max:255',
                'nombrecontacto' => 'required|string|max:255',
                'celularcontacto' => 'required|string|max:20',
                'parentescocontacto' => 'required|string|max:255',
                'nombrecolegio' => 'required|string|max:255',
                'caractercolegio' => 'required|in:Fiscal,Particular,Convenio',
                'egresocolegio' => 'required|integer|between:1900,2100',
                'diplomabachiller' => 'required|in:0,1',
                'path_ci_frontal' => 'required|image|mimes:jpg,jpeg,png,webp|max:350',
                'path_ci_reverso' => 'required|image|mimes:jpg,jpeg,png,webp|max:350',
                'path_nacimiento' => 'required|image|mimes:jpg,jpeg,png,webp|max:350',
                'path_diploma' => 'required|image|mimes:jpg,jpeg,png,webp|max:350',
            ];
        } else {
            $rules['ci'] = 'required|string|max:30|in:'.$personaExistente->ci;
        }

        $validated = $request->validate($rules, [
            'nombre.required' => 'Te faltó ingresar tu nombre.',
            'primerapellido.required' => 'Te faltó ingresar tu primer apellido.',
            'ci.required' => 'Te faltó ingresar tu CI.',
            'ci.unique' => 'Este CI ya está registrado en el sistema.',
            'fechanacimiento.required' => 'Te faltó indicar tu fecha de nacimiento.',
            'lugarnacimiento.required' => 'Te faltó ingresar tu lugar de nacimiento.',
            'nacionalidad.required' => 'Te faltó ingresar tu nacionalidad.',
            'sexo.required' => 'Te faltó seleccionar tu sexo.',
            'direccion.required' => 'Te faltó ingresar tu dirección.',
            'email.required' => 'Te faltó ingresar tu correo electrónico.',
            'email.email' => 'Ingresa un correo electrónico válido.',
            'nombrecontacto.required' => 'Te faltó ingresar el nombre de tu contacto.',
            'celularcontacto.required' => 'Te faltó ingresar el celular de contacto.',
            'parentescocontacto.required' => 'Te faltó indicar el parentesco del contacto.',
            'nombrecolegio.required' => 'Te faltó ingresar el nombre del colegio.',
            'caractercolegio.required' => 'Te faltó seleccionar el carácter del colegio.',
            'egresocolegio.required' => 'Te faltó indicar el año de egreso del colegio.',
            'fecha.required' => 'Te faltó indicar la fecha de inscripción.',
            'diplomabachiller.required' => 'Te faltó indicar si cuentas con diploma de bachiller.',
            'deposito.required' => 'Te faltó ingresar el número de comprobante del depósito.',
            'id_gestionacademica.required' => 'Te faltó seleccionar la gestión académica.',
            'id_carrera.required' => 'Te faltó seleccionar una carrera.',
            'path_ci_frontal.required' => 'Te faltó adjuntar el CI por el anverso.',
            'path_ci_frontal.image' => 'El CI por el anverso debe ser una imagen.',
            'path_ci_frontal.mimes' => 'El CI por el anverso debe estar en formato JPG, PNG o WebP.',
            'path_ci_frontal.max' => 'El CI por el anverso no debe superar 350 KB.',
            'path_ci_reverso.required' => 'Te faltó adjuntar el CI por el reverso.',
            'path_ci_reverso.image' => 'El CI por el reverso debe ser una imagen.',
            'path_ci_reverso.mimes' => 'El CI por el reverso debe estar en formato JPG, PNG o WebP.',
            'path_ci_reverso.max' => 'El CI por el reverso no debe superar 350 KB.',
            'path_nacimiento.required' => 'Te faltó adjuntar el certificado de nacimiento.',
            'path_nacimiento.image' => 'El certificado de nacimiento debe ser una imagen.',
            'path_nacimiento.mimes' => 'El certificado de nacimiento debe estar en formato JPG, PNG o WebP.',
            'path_nacimiento.max' => 'El certificado de nacimiento no debe superar 350 KB.',
            'path_diploma.required' => 'Te faltó adjuntar el diploma de bachiller.',
            'path_diploma.image' => 'El diploma de bachiller debe ser una imagen.',
            'path_diploma.mimes' => 'El diploma de bachiller debe estar en formato JPG, PNG o WebP.',
            'path_diploma.max' => 'El diploma de bachiller no debe superar 350 KB.',
            'path_bth.image' => 'El certificado BTH debe ser una imagen.',
            'path_bth.mimes' => 'El certificado BTH debe estar en formato JPG, PNG o WebP.',
            'path_bth.max' => 'El certificado BTH no debe superar 350 KB.',
            'nivelinicio.required' => 'Selecciona el año que cursará en esta carrera.',
        ]);

        $camposDocumento = [
            'path_ci_frontal' => 'path_ci',
            'path_ci_reverso' => 'path_ci_reverso',
            'path_nacimiento' => 'path_nacimiento',
            'path_diploma' => 'path_diploma',
            'path_bth' => 'path_bth',
        ];
        $pesoTotal = collect(array_keys($camposDocumento))
            ->filter(fn (string $campo): bool => $request->hasFile($campo))
            ->sum(fn (string $campo): int => $request->file($campo)->getSize());

        $limiteTotal = $estudianteExistente ? 350 : 1750;
        if ($pesoTotal > $limiteTotal * 1024) {
            return back()->withInput()->withErrors([
                'documentos' => "El total de los documentos no debe superar {$limiteTotal} KB.",
            ]);
        }

        $archivosGuardados = [];
        try {
            foreach ($camposDocumento as $campo => $columna) {
                if (! $request->hasFile($campo)) {
                    continue;
                }
                if ($estudianteExistente && $campo !== 'path_bth') {
                    continue;
                }

                $ruta = $request->file($campo)->store('estudiantes/'.now()->format('Y'), 'public');
                if (! $ruta) {
                    throw new \RuntimeException('No fue posible guardar uno de los documentos del estudiante.');
                }
                $archivosGuardados[$columna] = $ruta;
            }

            DB::transaction(function () use ($validated, $archivosGuardados, $estudianteExistente): void {
                $ahora = now();
                $idPersona = DB::table('personas')->where('ci', $validated['ci'])->lockForUpdate()->value('id_persona');
                if ($idPersona && DB::table('inscrito')
                    ->where('id_persona', $idPersona)
                    ->where('id_carrera', $validated['id_carrera'])
                    ->where('id_gestionacademica', $validated['id_gestionacademica'])
                    ->where('activo', true)
                    ->whereNull('id_inscrito_reemplazo')
                    ->exists()) {
                    throw \Illuminate\Validation\ValidationException::withMessages([
                        'id_carrera' => 'Esta persona ya está inscrita en esa carrera durante la gestión seleccionada.',
                    ]);
                }
                if (DB::table('inscrito')
                    ->where('id_gestionacademica', $validated['id_gestionacademica'])
                    ->where('activo', true)
                    ->whereRaw('LOWER(deposito) = ?', [mb_strtolower(trim($validated['deposito']))])
                    ->exists()) {
                    throw \Illuminate\Validation\ValidationException::withMessages([
                        'deposito' => 'Este comprobante ya está registrado en la gestión seleccionada.',
                    ]);
                }
                if (DB::table('comprobante')
                    ->where('id_gestionacademica', $validated['id_gestionacademica'])
                    ->where('activo', true)
                    ->whereRaw('LOWER(deposito) = ?', [mb_strtolower(trim($validated['deposito']))])
                    ->exists()) {
                    throw \Illuminate\Validation\ValidationException::withMessages([
                        'deposito' => 'Este comprobante ya está registrado en la gestión seleccionada.',
                    ]);
                }

                if (! $idPersona) {
                    $idPersona = DB::table('personas')->insertGetId([
                    'nombre' => $validated['nombre'],
                    'primerapellido' => $validated['primerapellido'],
                    'segundoapellido' => $validated['segundoapellido'] ?? null,
                    'ci' => $validated['ci'],
                    'lugarexpedicion' => $validated['lugarexpedicion'] ?? null,
                    'fechanacimiento' => $validated['fechanacimiento'],
                    'lugarnacimiento' => $validated['lugarnacimiento'],
                    'nacionalidad' => $validated['nacionalidad'],
                    'sexo' => $validated['sexo'],
                    'direccion' => $validated['direccion'],
                    'telefono' => $validated['telefono'] ?? null,
                    'celular' => $validated['celular'] ?? null,
                    'email' => $validated['email'],
                    'tipo' => 'Estudiante',
                    'created_at' => $ahora,
                    'updated_at' => $ahora,
                ]);
                }

                if (! DB::table('estudiante')->where('id_persona', $idPersona)->exists()) {
                    DB::table('estudiante')->insert([
                    'id_persona' => $idPersona,
                    'fecha' => $validated['fecha'],
                    'diplomabachiller' => (int) $validated['diplomabachiller'],
                    'nombrecontacto' => $validated['nombrecontacto'],
                    'celularcontacto' => $validated['celularcontacto'],
                    'parentescocontacto' => $validated['parentescocontacto'],
                    'nombrecolegio' => $validated['nombrecolegio'],
                    'caractercolegio' => $validated['caractercolegio'],
                    'egresocolegio' => $validated['egresocolegio'],
                    'becado' => (int) ($validated['becado'] ?? 0),
                    'deposito' => $validated['deposito'],
                    'traspaso_convalidacion' => (int) ($validated['traspaso_convalidacion'] ?? 0),
                    'id_carrera' => $validated['id_carrera'],
                    'id_gestionacademica' => $validated['id_gestionacademica'],
                    'matricula' => random_int(1000, 9999),
                    ...$archivosGuardados,
                    'created_at' => $ahora,
                    'updated_at' => $ahora,
                ]);
                } elseif (isset($archivosGuardados['path_bth'])) {
                    DB::table('estudiante')->where('id_persona', $idPersona)->update([
                        'path_bth' => $archivosGuardados['path_bth'],
                        'updated_at' => $ahora,
                    ]);
                }

                $inscripcionesPrevias = DB::table('inscrito')
                    ->where('id_persona', $idPersona)
                    ->where('id_carrera', $validated['id_carrera'])
                    ->count();

                DB::table('cursa')->updateOrInsert(
                    [
                        'id_persona' => $idPersona,
                        'id_carrera' => $validated['id_carrera'],
                        'id_gestionacademica' => $validated['id_gestionacademica'],
                    ],
                    ['nivelinicio' => $validated['nivelinicio'], 'created_at' => $ahora, 'updated_at' => $ahora]
                );

                $idInscrito = DB::table('inscrito')->insertGetId([
                    'codigo' => date('Y').$idPersona,
                    'fecha' => $validated['fecha'],
                    'numero' => $inscripcionesPrevias + 1,
                    'tipo' => $estudianteExistente ? 2 : 1,
                    'becado' => (int) ($validated['becado'] ?? 0),
                    'deposito' => $validated['deposito'],
                    'id_carrera' => $validated['id_carrera'],
                    'id_persona' => $idPersona,
                    'id_gestionacademica' => $validated['id_gestionacademica'],
                    'created_at' => $ahora,
                    'updated_at' => $ahora,
                ]);

                $monto = $this->montoInscripcion((int) $validated['id_carrera']);
                $idPago = DB::table('pago')->insertGetId([
                    'concepto' => null,
                    'monto' => $monto,
                    'pagado' => false,
                    'estado' => true,
                    'id_inscrito' => $idInscrito,
                    'created_at' => $ahora,
                    'updated_at' => $ahora,
                ]);
                DB::table('comprobante')->insert([
                    'deposito' => trim($validated['deposito']),
                    'fecha' => $validated['fecha'],
                    'valido' => false,
                    'id_inscrito' => $idInscrito,
                    'id_usuario' => auth()->id(),
                    'id_estudiante' => $idPersona,
                    'id_carrera' => $validated['id_carrera'],
                    'id_gestionacademica' => $validated['id_gestionacademica'],
                    'created_at' => $ahora,
                    'updated_at' => $ahora,
                ]);
                DB::table('detalle')->insert([
                    'nombre' => 'Inscripción',
                    'monto' => $monto,
                    'id_pago' => $idPago,
                    'created_at' => $ahora,
                    'updated_at' => $ahora,
                ]);
            });

            return redirect()->route('estudiante_nuevo.index')->with('success', $estudianteExistente
                ? 'La inscripción se registró usando la ficha existente del estudiante.'
                : 'Estudiante registrado correctamente.');
        } catch (\Illuminate\Validation\ValidationException $exception) {
            Storage::disk('public')->delete(array_values($archivosGuardados));
            throw $exception;
        } catch (\Throwable $exception) {
            Storage::disk('public')->delete(array_values($archivosGuardados));
            report($exception);

            return back()->withInput()->withErrors([
                'error' => 'No se pudo completar el registro. Verifica los datos e inténtalo nuevamente.',
            ]);
        }
    }
}