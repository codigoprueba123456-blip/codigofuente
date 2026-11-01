<?php

namespace Database\Seeders\Modulos;

use App\Models\Empleado;
use App\Models\Instituto;
use Illuminate\Database\Seeder;

class InstitucionalSeeder extends Seeder
{
    public function run(): void
    {
        $directorId = Empleado::query()
            ->where('codigo', 'EMP-DEMO-001')
            ->value('id_persona');
        $directorId ??= Empleado::query()
            ->where('codigo', 'EMP-PRUEBA-003')
            ->value('id_persona');

        Instituto::updateOrCreate(
            ['nombreinstituto' => 'Instituto de Prueba'],
            [
                'actividadeconomica' => 'Educación superior',
                'caracter' => 'PRIVADO',
                'nit' => '123456789',
                'nombreimpuestos' => 'Instituto de Prueba',
                'nombreregulador' => 'Ministerio de Educación',
                'preciocredito' => 100,
                'resolucionministerial' => 'R.M. 001/2026',
                'ubicacion' => 'La Paz, Bolivia',
                'abreviatura' => 'ITSA-DEMO',
                'telefono' => 2234567,
                'id_directoracademico' => $directorId,
                'id_rector' => $directorId,
            ]
        );
    }
}
