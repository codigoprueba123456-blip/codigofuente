<?php

namespace Database\Seeders\Modulos;

use Database\Seeders\Concerns\InsertsFilledRows;
use Illuminate\Database\Seeder;

class PagosSeeder extends Seeder
{
    use InsertsFilledRows;

    public function run(): void
    {
        // Completa los IDs de inscripción, postulante, usuario, estudiante o carrera cuando correspondan.
        $comprobantes = [
            [
                'codigo' => null,
                'deposito' => null,
                'fecha' => null, // AAAA-MM-DD HH:MM:SS
                'valido' => null,
                'id_inscrito' => null,
                'id_postulante' => null,
                'id_usuario' => null, // id_persona de users
                'id_estudiante' => null, // id_persona de estudiante
                'id_carrera' => null,
                'id_gestionacademica' => null,
            ],
        ];
        $pagos = [
            [
                'concepto' => null,
                'monto' => null,
                'pagado' => null,
                'estado' => null,
                'id_inscrito' => null,
                'id_postulante' => null,
            ],
        ];
        $detalles = [
            [
                'concepto' => null,
                'codigo' => null,
                'nombre' => null,
                'monto' => null,
                'id_comprobante' => null,
                'id_pago' => null,
            ],
        ];

        $this->insertFilledRows('comprobante', $comprobantes);
        $this->insertFilledRows('pago', $pagos);
        $this->insertFilledRows('detalle', $detalles);
    }
}
