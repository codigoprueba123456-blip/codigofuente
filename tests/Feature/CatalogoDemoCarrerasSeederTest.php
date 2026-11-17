<?php

namespace Tests\Feature;

use App\Models\Carrera;
use App\Models\GestionAcademica;
use App\Models\Grupo;
use App\Models\Instituto;
use App\Models\Materia;
use Database\Seeders\Modulos\CatalogoDemoCarrerasSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class CatalogoDemoCarrerasSeederTest extends TestCase
{
    use RefreshDatabase;

    private const NIVELES = [
        'ADM-2023' => [1, 2, 3],
        'COC-2023' => [1, 2, 3],
        'CNG-2023' => [1, 2, 3],
        'GAS-2023' => [1, 2, 3],
        'INF-2023' => [1, 2, 3, 4, 5, 6],
        'MTZ-2023' => [1, 2, 3],
        'SEJ-2023' => [1, 2, 3],
        'SIS-2023' => [1, 2, 3],
        'TOP-2023' => [1, 2, 3],
    ];

    public function test_it_completes_demo_curricula_and_groups_idempotently_without_overwriting_existing_data(): void
    {
        $this->crearCatalogoBase();

        $this->seed(CatalogoDemoCarrerasSeeder::class);
        $recuentosIniciales = [
            'materia' => Materia::count(),
            'grupo' => Grupo::count(),
        ];

        $this->assertSame(133, Materia::where('codigo', 'like', 'DEMO-%')->count());
        $this->assertSame(154, Grupo::where('codigo', 'like', 'DEMO-%')->count());
        $this->assertDatabaseHas('materia', [
            'codigo' => 'DEMO-INF-2023-N6-05',
            'nivel' => 6,
            'horas' => 64,
            'activo' => true,
        ]);
        $this->assertDatabaseHas('grupo', [
            'codigo' => 'DEMO-MTZ-2023-N3-01-B',
            'turno' => 3,
            'capacidad' => 30,
            'abierto' => true,
        ]);

        foreach (self::NIVELES as $codigoCarrera => $niveles) {
            $idCarrera = Carrera::where('codigo', $codigoCarrera)->value('id_carrera');

            foreach ($niveles as $nivel) {
                $this->assertDatabaseHas('materia', [
                    'id_carrera' => $idCarrera,
                    'nivel' => $nivel,
                    'activo' => true,
                    'curricular' => true,
                ]);
            }
        }

        $grupoExistente = Grupo::where('codigo', 'EXISTENTE-ADM-2023-N1-A')->firstOrFail();
        $this->assertSame(24, (int) $grupoExistente->capacidad);
        $this->assertSame(1, (int) $grupoExistente->turno);

        $this->seed(CatalogoDemoCarrerasSeeder::class);

        $this->assertSame($recuentosIniciales, [
            'materia' => Materia::count(),
            'grupo' => Grupo::count(),
        ]);
        $this->assertSame(24, (int) $grupoExistente->fresh()->capacidad);
        $this->assertSame(1, (int) $grupoExistente->fresh()->turno);
    }

    private function crearCatalogoBase(): void
    {
        $instituto = Instituto::create(['nombreinstituto' => 'Instituto de Prueba']);
        $gestion = GestionAcademica::create([
            'gestion' => 2026,
            'periodo' => 1,
            'regimen' => Carrera::REGIMEN_ANUAL,
            'vigente' => true,
            'activo' => true,
        ]);

        foreach (self::NIVELES as $codigo => $niveles) {
            Carrera::create([
                'codigo' => $codigo,
                'nombre' => $codigo,
                'activo' => true,
                'regimen' => $codigo === 'INF-2023'
                    ? Carrera::REGIMEN_SEMESTRAL
                    : Carrera::REGIMEN_ANUAL,
                'id_instituto' => $instituto->id_instituto,
            ]);
        }

        foreach ([
            'ADM-2023' => [1, 2, 3],
            'GAS-2023' => [1, 2, 3],
            'SEJ-2023' => [1, 2, 3],
            'SIS-2023' => [2, 3],
        ] as $codigoCarrera => $nivelesExistentes) {
            $idCarrera = Carrera::where('codigo', $codigoCarrera)->value('id_carrera');

            foreach ($nivelesExistentes as $nivel) {
                $materia = Materia::create([
                    'codigo' => "EXISTENTE-{$codigoCarrera}-N{$nivel}",
                    'nombre' => "Materia existente {$codigoCarrera} {$nivel}",
                    'curricular' => true,
                    'nivel' => $nivel,
                    'numero' => 1,
                    'activo' => true,
                    'id_carrera' => $idCarrera,
                ]);
                $esGrupoProtegido = $codigoCarrera === 'ADM-2023' && $nivel === 1;

                DB::table('grupo')->insert([
                    'codigo' => $materia->codigo.'-A',
                    'capacidad' => $esGrupoProtegido ? 24 : 30,
                    'turno' => $esGrupoProtegido ? 1 : 3,
                    'abierto' => true,
                    'activo' => true,
                    'id_gestionacademica' => $gestion->id_gestionacademica,
                    'id_materia' => $materia->id_materia,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }
}
