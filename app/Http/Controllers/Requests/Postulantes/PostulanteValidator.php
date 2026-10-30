<?php

namespace App\Http\Controllers\Requests\Postulantes;

use App\Models\GestionAcademica;
use App\Models\Postulante;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/**
 * Centraliza las reglas de entrada compartidas por el registro y la edición.
 */
class PostulanteValidator
{
    /**
     * Comprueba los campos recibidos antes de crear una postulación.
     */
    public function validarRegistro(
        Request $request,
        GestionAcademica $gestion,
        ?Postulante $postulanteExistente
    ): array {
        $request->merge(['id_gestionacademica' => $gestion->id_gestionacademica]);

        $rules = $this->reglasComunes();
        $rules['deposito'] = $this->reglaComprobanteUnico($gestion);
        $rules += $this->reglasDocumentosRequeridos($postulanteExistente);

        $datos = Validator::make($request->all(), $rules, [
            'deposito.unique' => 'El número de comprobante/depósito bancario ya ha sido utilizado en esta gestión académica. Debe utilizar un comprobante único y exclusivo por cada postulación.',
        ])->validate();

        $this->validarPesoTotalDocumentos($request);

        return $datos;
    }

    /**
     * Comprueba los campos recibidos al editar, excluyendo el comprobante actual.
     */
    public function validarActualizacion(
        Request $request,
        GestionAcademica $gestion,
        Postulante $postulante
    ): array {
        $request->merge(['id_gestionacademica' => $gestion->id_gestionacademica]);

        $rules = $this->reglasComunes($postulante->id_carrera);
        $rules['deposito'] = $this->reglaComprobanteUnico($gestion, $postulante);
        $rules += $this->reglasDocumentosOpcionales();

        $datos = Validator::make($request->all(), $rules, [
            'deposito.unique' => 'El número de comprobante/depósito bancario ya se encuentra registrado por otro postulante en esta gestión académica.',
        ])->validate();

        $this->validarPesoTotalDocumentos($request);

        return $datos;
    }

    private function reglasComunes(?int $carreraActualId = null): array
    {
        $carreraActiva = Rule::exists('carreras', 'id_carrera')
            ->where(function ($query) use ($carreraActualId): void {
                $query->where('activo', true);

                if ($carreraActualId !== null) {
                    $query->orWhere('id_carrera', $carreraActualId);
                }
            });

        return [
            'nombre' => ['required', 'string', 'max:100'],
            'primerapellido' => ['required', 'string', 'max:100'],
            'segundoapellido' => ['nullable', 'string', 'max:100'],
            'ci' => ['required', 'string', 'max:30'],
            'lugarexpedicion' => ['required', 'string', 'max:20'],
            'fechanacimiento' => ['required', 'date'],
            'lugarnacimiento' => ['required', 'string', 'max:255'],
            'nacionalidad' => ['required', 'string', 'max:100'],
            'sexo' => ['required', 'in:M,F'],
            'direccion' => ['required', 'string', 'max:255'],
            'telefono' => ['nullable', 'string', 'max:20'],
            'celular' => ['required', 'string', 'max:20'],
            'email' => ['required', 'email', 'max:255'],
            'nombrecontacto' => ['required', 'string', 'max:255'],
            'celularcontacto' => ['required', 'string', 'max:255'],
            'parentescocontacto' => ['required', 'string', 'max:255'],
            'nombrecolegio' => ['required', 'string', 'max:255'],
            'caractercolegio' => ['required', 'in:Fiscal,Particular,Convenio'],
            'egresocolegio' => ['required', 'integer', 'between:1900,2100'],
            'id_gestionacademica' => ['required', 'exists:gestionacademica,id_gestionacademica'],
            'id_carrera' => ['required', $carreraActiva],
            'diplomabachiller' => ['nullable', 'boolean'],
        ];
    }

    private function reglaComprobanteUnico(
        GestionAcademica $gestion,
        ?Postulante $postulanteActual = null
    ): array {
        $regla = Rule::unique('postulante', 'deposito')
            ->where(fn ($query) => $query->where('id_gestionacademica', $gestion->id_gestionacademica));

        if ($postulanteActual) {
            $regla->ignore($postulanteActual->id_postulante, 'id_postulante');
        }

        return ['required', 'string', 'max:255', $regla];
    }

    private function reglasDocumentosRequeridos(?Postulante $postulanteExistente): array
    {
        return [
            'fotografia' => [$postulanteExistente ? 'nullable' : 'required', 'image', 'mimes:jpg,jpeg,png', 'max:150'],
            'path_ci_frontal' => [
                $postulanteExistente && $postulanteExistente->path_ci_frontal ? 'nullable' : 'required',
                'image',
                'mimes:jpg,jpeg,png',
                'max:350',
            ],
            'path_ci_reverso' => [
                $postulanteExistente && $postulanteExistente->path_ci_reverso ? 'nullable' : 'required',
                'image',
                'mimes:jpg,jpeg,png',
                'max:350',
            ],
            'path_nacimiento' => [$postulanteExistente ? 'nullable' : 'required', 'image', 'mimes:jpg,jpeg,png', 'max:350'],
            'path_diploma' => [$postulanteExistente ? 'nullable' : 'required', 'image', 'mimes:jpg,jpeg,png', 'max:350'],
        ];
    }

    private function reglasDocumentosOpcionales(): array
    {
        return [
            'fotografia' => ['nullable', 'image', 'mimes:jpg,jpeg,png', 'max:150'],
            'path_ci_frontal' => ['nullable', 'image', 'mimes:jpg,jpeg,png', 'max:350'],
            'path_ci_reverso' => ['nullable', 'image', 'mimes:jpg,jpeg,png', 'max:350'],
            'path_nacimiento' => ['nullable', 'image', 'mimes:jpg,jpeg,png', 'max:350'],
            'path_diploma' => ['nullable', 'image', 'mimes:jpg,jpeg,png', 'max:350'],
        ];
    }

    private function validarPesoTotalDocumentos(Request $request): void
    {
        $camposArchivo = [
            'fotografia',
            'path_ci_frontal',
            'path_ci_reverso',
            'path_nacimiento',
            'path_diploma',
        ];

        $pesoTotal = collect($camposArchivo)
            ->sum(fn (string $campo) => $request->file($campo)?->getSize() ?? 0);

        if ($pesoTotal > 1550 * 1024) {
            throw ValidationException::withMessages([
                'documentos' => 'El conjunto de imágenes supera el límite total de 1.550 KB. Vuelve a seleccionar imágenes más pequeñas.',
            ]);
        }
    }
}
