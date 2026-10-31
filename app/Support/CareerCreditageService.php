<?php

namespace App\Support;

use App\Models\Carrera;
use App\Models\Instituto;
use App\Models\Materia;
use Illuminate\Support\Facades\DB;

class CareerCreditageService
{
    public function syncCareer(Carrera $carrera): void
    {
        DB::transaction(fn () => $this->recalculate($carrera));
    }

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

    private function recalculate(Carrera $carrera): void
    {
        $precioCredito = (int) ($carrera->instituto()->value('preciocredito') ?? 0);
        $creditajeTotal = $precioCredito * 10;

        $carrera->update([
            'creditajeadmision' => $creditajeTotal,
            'creditajematricula' => $creditajeTotal,
        ]);

        $materias = Materia::query()
            ->where('id_carrera', $carrera->id_carrera)
            ->where('curricular', true)
            ->orderBy('nivel')
            ->orderBy('numero')
            ->orderBy('id_materia')
            ->get();

        Materia::query()
            ->where('id_carrera', $carrera->id_carrera)
            ->where('curricular', false)
            ->update(['creditajemateria' => null]);

        $cantidadMaterias = $materias->count();
        if ($cantidadMaterias === 0) {
            return;
        }

        $creditosPorMateria = intdiv($creditajeTotal, $cantidadMaterias);
        $creditosSobrantes = $creditajeTotal % $cantidadMaterias;

        foreach ($materias as $index => $materia) {
            $materia->update([
                'creditajemateria' => $creditosPorMateria + ($index < $creditosSobrantes ? 1 : 0),
            ]);
        }
    }
}
