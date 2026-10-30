<?php

namespace Database\Seeders\Modulos;

use Database\Seeders\Concerns\InsertsFilledRows;
use Illuminate\Database\Seeder;

class InstitucionalSeeder extends Seeder
{
    use InsertsFilledRows;

    public function run(): void
    {
        // id_directoracademico e id_rector deben ser id_persona de empleados existentes.
        $institutos = [
            [
                'actividadeconomica' => null,
                'caracter' => null,
                'logo' => null, // Ruta relativa del archivo
                'nit' => null,
                'nombreimpuestos' => null,
                'nombreregulador' => null,
                'preciocredito' => null,
                'resolucionministerial' => null,
                'ubicacion' => null,
                'abreviatura' => null,
                'nombreinstituto' => null,
                'telefono' => null,
                'id_directoracademico' => null,
                'id_rector' => null,
            ],
        ];

        $this->insertFilledRows('instituto', $institutos);
    }
}
