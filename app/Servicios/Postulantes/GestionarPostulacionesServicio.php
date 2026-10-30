<?php

namespace App\Servicios\Postulantes;

use App\Http\Controllers\Requests\Postulantes\PostulanteValidator;
use App\Models\Carrera;
use App\Models\GestionAcademica;
use App\Models\Postulante;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Coordina las reglas y la persistencia de las postulaciones de una persona.
 */
class GestionarPostulacionesServicio
{
    public function __construct(
        private readonly PostulanteValidator $validador
    ) {}

    /**
     * Crea una postulación nueva y conserva los documentos de otra carrera si corresponde.
     */
    public function registrar(Request $request, GestionAcademica $gestion): Postulante
    {
        $request->merge(['id_gestionacademica' => $gestion->id_gestionacademica]);
        $ci = $this->normalizarCi((string) $request->input('ci', ''));

        $this->validarCarreraNoRepetida($ci, $request->input('id_carrera'), $gestion);

        $postulanteExistente = Postulante::where('ci', $ci)
            ->where('id_gestionacademica', $gestion->id_gestionacademica)
            ->first();

        $datos = $this->validador->validarRegistro($request, $gestion, $postulanteExistente);
        $this->validarVoucherDisponible(
            (string) $datos['deposito'],
            $gestion,
            $postulanteExistente
        );
        $datos = $this->prepararDocumentos($request, $datos, $ci, $gestion, $postulanteExistente);
        $datos['ci'] = $ci;
        $datos['diplomabachiller'] = $request->boolean('diplomabachiller');

        // El registro y el código consecutivo deben confirmarse juntos o revertirse juntos.
        return DB::transaction(function () use ($datos, $gestion): Postulante {
            $postulante = Postulante::create($datos);
            $postulante->update([
                'codigo' => 'POST-'.now()->format('Y').'-'.str_pad(
                    (string) $postulante->id_postulante,
                    5,
                    '0',
                    STR_PAD_LEFT
                ),
            ]);

            $monto = (float) (DB::table('carreras as c')
                ->join('instituto as i', 'i.id_instituto', '=', 'c.id_instituto')
                ->where('c.id_carrera', $postulante->id_carrera)
                ->value(DB::raw('COALESCE(c.creditajematricula, 0) * COALESCE(i.preciocredito, 0)')) ?? 0);
            $idPago = DB::table('pago')->insertGetId([
                'concepto' => null,
                'monto' => $monto,
                'pagado' => false,
                'estado' => true,
                'id_postulante' => $postulante->id_postulante,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            DB::table('comprobante')->insert([
                'deposito' => $postulante->deposito,
                'fecha' => $postulante->fecha,
                'valido' => false,
                'id_postulante' => $postulante->id_postulante,
                'id_usuario' => auth()->id(),
                'id_carrera' => $postulante->id_carrera,
                'id_gestionacademica' => $gestion->id_gestionacademica,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            DB::table('detalle')->insert([
                'concepto' => null,
                'codigo' => $postulante->codigo,
                'nombre' => 'Inscripción de postulante',
                'monto' => $monto,
                'id_pago' => $idPago,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            return $postulante;
        });
    }

    /**
     * Actualiza únicamente la postulación solicitada y conserva archivos no reemplazados.
     */
    public function actualizar(
        Request $request,
        Postulante $postulante,
        GestionAcademica $gestion
    ): Postulante {
        $request->merge(['id_gestionacademica' => $gestion->id_gestionacademica]);
        $ci = $this->normalizarCi((string) $request->input('ci', $postulante->ci));

        $this->validarCarreraNoRepetida(
            $ci,
            $request->input('id_carrera'),
            $gestion,
            $postulante
        );

        $datos = $this->validador->validarActualizacion($request, $gestion, $postulante);
        $this->validarVoucherDisponible((string) $datos['deposito'], $gestion, $postulante);
        $datos = $this->prepararDocumentos($request, $datos, $ci, $gestion, $postulante);
        $datos['ci'] = $ci;
        $datos['diplomabachiller'] = $request->boolean('diplomabachiller');

        $postulante->update($datos);
        DB::table('comprobante')->where('id_postulante', $postulante->id_postulante)->update([
            'deposito' => $postulante->deposito,
            'fecha' => $postulante->fecha,
            'updated_at' => now(),
        ]);

        return $postulante;
    }

    private function normalizarCi(string $ci): string
    {
        $ciLimpio = preg_replace('/[^A-Za-z0-9\\-]/', '', $ci) ?? '';

        if ($ciLimpio === '' || trim($ciLimpio, '-') === '') {
            throw ValidationException::withMessages([
                'ci' => 'Ingrese un C.I. válido con letras o números.',
            ]);
        }

        return $ciLimpio;
    }

    private function validarVoucherDisponible(
        string $voucher,
        GestionAcademica $gestion,
        ?Postulante $postulanteActual = null
    ): void {
        $consulta = DB::table('comprobante')
            ->where('id_gestionacademica', $gestion->id_gestionacademica)
            ->whereRaw('LOWER(deposito) = ?', [mb_strtolower(trim($voucher))]);

        if ($postulanteActual) {
            $consulta->where(function ($query) use ($postulanteActual): void {
                $query->whereNull('id_postulante')
                    ->orWhere('id_postulante', '!=', $postulanteActual->id_postulante);
            });
        }

        if ($consulta->exists()) {
            throw ValidationException::withMessages([
                'deposito' => 'Este comprobante ya está registrado en la gestión seleccionada.',
            ]);
        }
    }

    private function validarCarreraNoRepetida(
        string $ci,
        mixed $carreraId,
        GestionAcademica $gestion,
        ?Postulante $postulanteActual = null
    ): void {
        $consulta = Postulante::where('ci', $ci)
            ->where('id_carrera', $carreraId)
            ->where('id_gestionacademica', $gestion->id_gestionacademica);

        if ($postulanteActual) {
            $consulta->where('id_postulante', '!=', $postulanteActual->id_postulante);
        }

        if (! $consulta->exists()) {
            return;
        }

        if (! $postulanteActual) {
            $carrera = Carrera::where('id_carrera', $carreraId)->first();
            $nombreCarrera = $carrera ? $carrera->nombre : 'la carrera seleccionada';
            $mensaje = "El estudiante con C.I. {$ci} ya se encuentra registrado en {$nombreCarrera} para esta gestión académica. No está permitido registrarse dos veces en la misma carrera.";
        } else {
            $mensaje = "El estudiante con C.I. {$ci} ya se encuentra registrado en esta carrera para esta gestión académica. No está permitido registrarse dos veces en la misma carrera.";
        }

        throw ValidationException::withMessages(['id_carrera' => $mensaje]);
    }

    private function prepararDocumentos(
        Request $request,
        array $datos,
        string $ci,
        GestionAcademica $gestion,
        ?Postulante $postulanteExistente
    ): array {
        $directorio = 'postulantes/'.date('Y').'_G'.$gestion->id_gestionacademica;
        $documentos = [
            'fotografia' => ['foto', "{$directorio}/{$ci}_foto"],
            'path_ci_frontal' => ['path_ci_frontal', "{$directorio}/{$ci}_ci_frontal"],
            'path_ci_reverso' => ['path_ci_reverso', "{$directorio}/{$ci}_ci_reverso"],
            'path_nacimiento' => ['path_nacimiento', "{$directorio}/{$ci}_nacimiento"],
            'path_diploma' => ['path_diploma', "{$directorio}/{$ci}_diploma"],
        ];

        // En una carrera adicional se conservan los documentos existentes si no se vuelven a cargar.
        foreach ($documentos as $campo => [$atributo, $rutaBase]) {
            if ($request->hasFile($campo)) {
                $datos[$atributo] = $this->guardarImagen($request->file($campo), $rutaBase);
            } elseif ($postulanteExistente) {
                $datos[$atributo] = $postulanteExistente->$atributo;
            }
        }

        unset($datos['fotografia']);

        return $datos;
    }

    private function guardarImagen($archivo, string $rutaBase): string
    {
        $extension = match ($archivo->getMimeType()) {
            'image/webp' => 'webp',
            'image/png' => 'png',
            default => 'jpg',
        };

        $ruta = $archivo->storeAs(
            dirname($rutaBase),
            basename($rutaBase).'.'.$extension,
            'public'
        );

        if (! $ruta) {
            throw new \RuntimeException('No fue posible guardar una de las imágenes del postulante.');
        }

        return $ruta;
    }
}
