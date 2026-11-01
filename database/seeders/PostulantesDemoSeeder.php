<?php

namespace Database\Seeders;

use App\Models\Carrera;
use App\Models\GestionAcademica;
use App\Models\Postulante;
use Illuminate\Database\Seeder;

class PostulantesDemoSeeder extends Seeder
{
    public function run(): void
    {
        $gestion = GestionAcademica::where('gestion', now()->year)
            ->where('periodo', 1)
            ->firstOrFail();
        $sistemas = Carrera::where('codigo', 'DEMO-SIS')->firstOrFail();
        $mecanica = Carrera::where('codigo', 'DEMO-MTZ')->firstOrFail();

        $postulanteUno = [
            'ci' => 'VALERIA-001',
            'primerapellido' => 'Quispe',
            'segundoapellido' => 'Mamani',
            'nombre' => 'Valeria Alejandra',
            'celular' => '70000001',
            'telefono' => '2000001',
            'email' => 'valeria.quispe@example.com',
            'direccion' => 'Zona Villa Adela, El Alto, La Paz',
            'fechanacimiento' => '2005-04-15',
            'lugarnacimiento' => 'El Alto, La Paz',
            'lugarexpedicion' => 'LP',
            'nacionalidad' => 'Boliviana',
            'sexo' => 'F',
            'nombrecolegio' => 'U.E. Demo El Alto',
            'caractercolegio' => 'Fiscal',
            'egresocolegio' => 2024,
            'diplomabachiller' => true,
            'nombrecontacto' => 'Rosa Mamani',
            'parentescocontacto' => 'Madre',
            'celularcontacto' => '70000011',
            'path_ci_frontal' => 'demo/valeria_quispe_ci_frontal.jpg',
            'path_ci_reverso' => 'demo/valeria_quispe_ci_reverso.jpg',
            'path_nacimiento' => 'demo/valeria_quispe_certificado_nacimiento.jpg',
            'path_diploma' => 'demo/valeria_quispe_diploma_bachiller.jpg',
        ];

        $this->crearPostulacion(
            $postulanteUno,
            $sistemas,
            $gestion,
            'DEMO-POST-001',
            'DEMO-PAGO-2026-001'
        );

        $postulanteDos = [
            'ci' => 'DIEGO-002',
            'primerapellido' => 'Flores',
            'segundoapellido' => 'Choque',
            'nombre' => 'Diego Armando',
            'celular' => '70000002',
            'telefono' => '4000002',
            'email' => 'diego.flores@example.com',
            'direccion' => 'Zona Cala Cala, Cochabamba',
            'fechanacimiento' => '2004-09-22',
            'lugarnacimiento' => 'Cochabamba',
            'lugarexpedicion' => 'CB',
            'nacionalidad' => 'Boliviana',
            'sexo' => 'M',
            'nombrecolegio' => 'U.E. Demo Cochabamba',
            'caractercolegio' => 'Fiscal',
            'egresocolegio' => 2023,
            'diplomabachiller' => true,
            'nombrecontacto' => 'Elena Choque',
            'parentescocontacto' => 'Madre',
            'celularcontacto' => '70000012',
            'path_ci_frontal' => 'demo/diego_flores_ci_frontal.jpg',
            'path_ci_reverso' => 'demo/diego_flores_ci_reverso.jpg',
            'path_nacimiento' => 'demo/diego_flores_certificado_nacimiento.jpg',
            'path_diploma' => 'demo/diego_flores_diploma_bachiller.jpg',
        ];

        $this->crearPostulacion(
            $postulanteDos,
            $sistemas,
            $gestion,
            'DEMO-POST-002-SIS',
            'DEMO-PAGO-2026-002'
        );
        $this->crearPostulacion(
            $postulanteDos,
            $mecanica,
            $gestion,
            'DEMO-POST-002-MTZ',
            'DEMO-PAGO-2026-003'
        );
    }

    private function crearPostulacion(
        array $datosPostulante,
        Carrera $carrera,
        GestionAcademica $gestion,
        string $codigo,
        string $deposito
    ): void {
        Postulante::updateOrCreate(
            [
                'ci' => $datosPostulante['ci'],
                'id_carrera' => $carrera->id_carrera,
                'id_gestionacademica' => $gestion->id_gestionacademica,
            ],
            array_merge($datosPostulante, [
                'codigo' => $codigo,
                'fecha' => now(),
                'deposito' => $deposito,
            ])
        );
    }
}
