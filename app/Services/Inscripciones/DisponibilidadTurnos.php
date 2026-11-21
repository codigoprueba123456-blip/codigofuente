<?php

namespace App\Services\Inscripciones;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class DisponibilidadTurnos
{
    /**
     * Agrupa los grupos abiertos por turno y paralelo y calcula sus cupos disponibles.
     * Puede excluir la postulación o inscripción que se está validando para no contarla dos veces.
     */
    public function opciones(
        int $carreraId,
        int $gestionId,
        ?int $postulanteExcluir = null,
        ?int $nivel = null,
        ?int $inscritoExcluir = null
    ): array
    {
        $grupos = collect($this->gruposAbiertos($carreraId, $gestionId, $nivel));

        return $grupos
            ->groupBy(fn (array $grupo): string => $grupo['turno'].'-'.$grupo['paralelo'])
            ->map(function (Collection $grupos, string $clave) use ($carreraId, $gestionId, $postulanteExcluir, $inscritoExcluir): array {
                [$turno, $paralelo] = explode('-', $clave, 2);
                $capacidades = $grupos->pluck('capacidad')->filter(fn ($capacidad): bool => $capacidad !== null);
                $capacidad = $capacidades->isNotEmpty() ? (int) $capacidades->min() : null;
                $ocupados = $this->ocupacion(
                    (int) $carreraId,
                    (int) $gestionId,
                    (int) $turno,
                    $paralelo,
                    $postulanteExcluir,
                    $inscritoExcluir
                );

                return [
                    'turno' => (int) $turno,
                    'paralelo' => $paralelo,
                    'capacidad' => $capacidad,
                    'disponibles' => $capacidad === null ? null : max(0, $capacidad - $ocupados),
                ];
            })
            ->filter(fn (array $opcion): bool => $opcion['disponibles'] === null || $opcion['disponibles'] > 0)
            ->sortBy(fn (array $opcion): string => sprintf('%d-%s', $opcion['turno'], $opcion['paralelo']))
            ->values()
            ->all();
    }

    /**
     * Devuelve los grupos activos y abiertos de una carrera, gestión y nivel determinados.
     */
    public function gruposAbiertos(int $carreraId, int $gestionId, ?int $nivel = null): array
    {
        return $this->normalizarGrupos(
            $this->gruposDeCarrera($carreraId, $gestionId, nivel: $nivel)
        )->all();
    }

    /**
     * Confirma que el turno y paralelo elegidos sigan abiertos para la carrera y el nivel.
     */
    public function validarGrupoAbierto(
        int $carreraId,
        int $gestionId,
        int $turno,
        string $paralelo,
        ?int $nivel = null
    ): void {
        $opciones = $this->normalizarGrupos(
            $this->gruposDeCarrera($carreraId, $gestionId, lock: true, nivel: $nivel)
        );
        $disponible = $opciones->contains(fn (array $opcion): bool =>
            $opcion['turno'] === $turno
            && $opcion['paralelo'] === mb_strtoupper(trim($paralelo))
        );

        if (! $disponible) {
            throw ValidationException::withMessages([
                'turno' => 'El turno y paralelo seleccionados ya no están abiertos para esta carrera y nivel en la gestión vigente.',
            ]);
        }
    }

    /**
     * Confirma que el turno y paralelo tengan grupos habilitados y cupos libres.
     * Vuelve a consultar los grupos con bloqueo para reducir conflictos entre inscripciones simultáneas.
     */
    public function validarDisponible(
        int $carreraId,
        int $gestionId,
        int $turno,
        string $paralelo,
        ?int $postulanteExcluir = null,
        ?int $nivel = null,
        ?int $inscritoExcluir = null
    ): void {
        $this->gruposDeCarrera($carreraId, $gestionId, lock: true, nivel: $nivel);

        $opcion = collect($this->opciones(
            $carreraId,
            $gestionId,
            $postulanteExcluir,
            $nivel,
            $inscritoExcluir
        ))
            ->first(fn (array $opcion): bool =>
                $opcion['turno'] === $turno
                && $opcion['paralelo'] === mb_strtoupper(trim($paralelo))
            );

        if (! $opcion) {
            throw ValidationException::withMessages([
                'turno' => 'El turno y paralelo seleccionados no están habilitados o ya no tienen cupos disponibles para esta carrera.',
            ]);
        }
    }

    /**
     * Consulta los grupos activos de una carrera y gestión, con opción de limitar por nivel o bloquear filas.
     */
    private function gruposDeCarrera(
        int $carreraId,
        int $gestionId,
        bool $lock = false,
        ?int $nivel = null
    ): Collection
    {
        $consulta = DB::table('grupo as g')
            ->join('materia as m', 'm.id_materia', '=', 'g.id_materia')
            ->where('m.id_carrera', $carreraId)
            ->where('m.activo', true)
            ->where('g.id_gestionacademica', $gestionId)
            ->where('g.activo', true)
            ->where('g.abierto', true)
            ->whereIn('g.turno', [1, 2, 3])
            ->whereNotNull('g.codigo')
            ->select(['g.id_grupo', 'g.codigo', 'g.capacidad', 'g.turno']);

        if ($nivel !== null) {
            $consulta->where('m.nivel', $nivel);
        }

        if ($lock) {
            $consulta->lockForUpdate();
        }

        return $consulta->get();
    }

    /**
     * Convierte los códigos de grupo reconocidos en pares de turno y letra de paralelo.
     */
    private function normalizarGrupos(Collection $grupos): Collection
    {
        return $grupos
            ->map(function (object $grupo): ?array {
                if (! preg_match('/(?:^|[-_])([a-z])$/i', trim((string) $grupo->codigo), $coincidencia)) {
                    return null;
                }

                return [
                    'turno' => (int) $grupo->turno,
                    'paralelo' => mb_strtoupper($coincidencia[1]),
                    'capacidad' => $grupo->capacidad !== null ? (int) $grupo->capacidad : null,
                ];
            })
            ->filter()
            ->values();
    }

    /**
     * Cuenta postulaciones pendientes e inscripciones activas para una combinación de grupo.
     * Las postulaciones ya convertidas se excluyen y se puede omitir el registro que se está actualizando.
     */
    private function ocupacion(
        int $carreraId,
        int $gestionId,
        int $turno,
        string $paralelo,
        ?int $postulanteExcluir,
        ?int $inscritoExcluir
    ): int {
        $postulaciones = DB::table('postulante')
            ->where('id_carrera', $carreraId)
            ->where('id_gestionacademica', $gestionId)
            ->where('turno', $turno)
            ->where('paralelo', $paralelo)
            ->whereNotExists(function ($query): void {
                $query->selectRaw('1')
                    ->from('inscrito as inscritos_convertidos')
                    ->whereColumn('inscritos_convertidos.id_postulante_origen', 'postulante.id_postulante')
                    ->where('inscritos_convertidos.activo', true)
                    ->whereNull('inscritos_convertidos.id_inscrito_reemplazo');
            });

        if ($postulanteExcluir !== null) {
            $postulaciones->where('id_postulante', '!=', $postulanteExcluir);
        }

        $inscritos = DB::table('inscrito')
            ->where('id_carrera', $carreraId)
            ->where('id_gestionacademica', $gestionId)
            ->where('turno', $turno)
            ->where('paralelo', $paralelo)
            ->where('activo', true)
            ->whereNull('id_inscrito_reemplazo')
            ->when(
                $inscritoExcluir !== null,
                fn ($query) => $query->where('id_inscrito', '<>', $inscritoExcluir)
            )
            ->count();

        return $postulaciones->count() + $inscritos;
    }
}
