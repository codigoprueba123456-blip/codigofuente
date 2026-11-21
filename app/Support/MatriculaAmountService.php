<?php

namespace App\Support;

use App\Models\TarifaMatricula;
use Illuminate\Support\Facades\DB;

class MatriculaAmountService
{
    /**
     * Calcula el monto de matrícula siguiendo la prioridad de tarifas configuradas.
     * Busca primero la combinación exacta de gestión/carrera/nivel, después la tarifa general de nivel cero
     * y, si ninguna existe, conserva la fórmula histórica: creditaje de matrícula por precio del crédito.
     */
    public function calcular(int $idCarrera, int $idGestion, int $nivel): float
    {
        $tarifaPorNivel = TarifaMatricula::query()
            ->where('id_gestionacademica', $idGestion)
            ->where('id_carrera', $idCarrera)
            ->where('nivel', $nivel)
            ->value('monto');

        if ($tarifaPorNivel !== null) {
            return (float) $tarifaPorNivel;
        }

        $tarifaGeneral = TarifaMatricula::query()
            ->where('id_gestionacademica', $idGestion)
            ->where('id_carrera', $idCarrera)
            ->where('nivel', 0)
            ->value('monto');

        if ($tarifaGeneral !== null) {
            return (float) $tarifaGeneral;
        }

        $montoActual = DB::table('carreras as c')
            ->join('instituto as i', 'i.id_instituto', '=', 'c.id_instituto')
            ->where('c.id_carrera', $idCarrera)
            ->value(DB::raw('COALESCE(c.creditajematricula, 0) * COALESCE(i.preciocredito, 0)'));

        return (float) ($montoActual ?? 0);
    }
}
