<?php

namespace App\Support;

use App\Models\Carrera;
use App\Models\Instituto;
use App\Models\Materia;
use Illuminate\Support\Facades\DB;

class CareerCreditageService
{
    /**
     * Ejecuta el recálculo de una carrera dentro de una transacción.
     * Agrupa la actualización de los valores de la carrera y la distribución entre materias,
     * de modo que un error no deje guardada una distribución parcial.
     */
    public function syncCareer(Carrera $carrera): void
    {
        DB::transaction(fn () => $this->recalculate($carrera));
    }

    /**
     * Recorre en orden estable todas las carreras del instituto y recalcula sus importes y materias.
     * La transacción incluye el lote completo para no dejar algunas carreras con el precio anterior.
     */
    public function syncInstitute(Instituto $instituto): void
    {
        DB::transaction(function () use ($instituto): void {
            Carrera::query()
                ->where('id_instituto', $instituto->id_instituto)
                ->orderBy('id_carrera')
                ->get()
                ->each(fn (Carrera $carrera) => $this->recalculate($carrera));
        });
    }

    /**
     * Obtiene el precio por crédito institucional y determina los creditajes de admisión y matrícula.
     * Cada campo manual puede sobrescribir por separado el valor automático; el total curricular se
     * reparte entre materias curriculares activas, mientras las no curriculares quedan sin creditaje.
     * Los sobrantes de la división se asignan en orden de nivel, número e identificador.
     */
    private function recalculate(Carrera $carrera): void
    {
        $precioCredito = (int) ($carrera->instituto()->value('preciocredito') ?? 0);
        $creditajeTotal = $precioCredito * 10;

        // El valor automático base es precio del crédito del instituto por diez. Admisión y matrícula
        // pueden sobrescribirse por separado; esos valores manuales no cambian el reparto por materia.
        $carrera->update([
            'creditajeadmision' => $carrera->creditajeadmision_manual ?? $creditajeTotal,
            'creditajematricula' => $carrera->creditajematricula_manual ?? $creditajeTotal,
        ]);

        $materias = Materia::query()
            ->where('id_carrera', $carrera->id_carrera)
            ->where('activo', true)
            ->where('curricular', true)
            ->orderBy('nivel')
            ->orderBy('numero')
            ->orderBy('id_materia')
            ->get();

        // Las materias no curriculares no participan en el reparto y no deben conservar un valor anterior.
        Materia::query()
            ->where('id_carrera', $carrera->id_carrera)
            ->where('curricular', false)
            ->update(['creditajemateria' => null]);

        $cantidadMaterias = $materias->count();
        if ($cantidadMaterias === 0) {
            // No hay materias a las que distribuir; los creditajes de admisión y matrícula ya quedaron actualizados.
            return;
        }

        $creditosPorMateria = intdiv($creditajeTotal, $cantidadMaterias);
        $creditosSobrantes = $creditajeTotal % $cantidadMaterias;

        // La división puede dejar créditos sobrantes; se suma uno a las primeras materias del orden
        // nivel/número/ID para que la distribución sea repetible y la suma coincida con el total.
        foreach ($materias as $index => $materia) {
            $materia->update([
                'creditajemateria' => $creditosPorMateria + ($index < $creditosSobrantes ? 1 : 0),
            ]);
        }
    }
}
