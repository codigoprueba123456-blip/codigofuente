<?php

namespace Tests\Feature;

use App\Models\Carrera;
use App\Models\GestionAcademica;
use App\Models\Instituto;
use App\Models\Persona;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class PagosIndexTest extends TestCase
{
    use RefreshDatabase;

    public function test_payment_history_lists_student_payments_with_or_without_a_receipt(): void
    {
        $this->withoutMiddleware();
        $fixture = $this->createStudentPaymentFixture();

        $response = $this->get(route('pagos.index'));

        $response->assertOk()
            ->assertSee('María Quispe')
            ->assertSee('estudiante_20260010')
            ->assertSee('DEP-2026-001')
            ->assertSee('250.00')
            ->assertSee('Exportar Excel')
            ->assertSee('Exportar PDF')
            ->assertDontSee('aria-label="Exportar pagos"', false);

        $pagos = $response->viewData('pagos')->getCollection();
        $this->assertCount(2, $pagos);
        $this->assertSame(
            [$fixture['payment_with_receipt'], $fixture['payment_without_receipt']],
            $pagos->pluck('id_pago')->sort()->values()->all()
        );
    }

    public function test_user_and_general_search_match_student_identity_and_preserve_filters_in_pdf_export(): void
    {
        $this->withoutMiddleware();
        $this->createStudentPaymentFixture();

        $byFullName = $this->get(route('pagos.index', ['usuario' => 'María Quispe']));
        $byFullName->assertOk();
        $this->assertCount(2, $byFullName->viewData('pagos')->getCollection());

        $byUsername = $this->get(route('pagos.index', ['usuario' => 'estudiante_20260010']));
        $byUsername->assertOk();
        $this->assertCount(2, $byUsername->viewData('pagos')->getCollection());

        $byIdentityNumber = $this->get(route('pagos.index', ['buscar' => 'CI-20260010']));
        $byIdentityNumber->assertOk();
        $this->assertCount(2, $byIdentityNumber->viewData('pagos')->getCollection());

        $byDeposit = $this->get(route('pagos.index', [
            'usuario' => 'CI-20260010',
            'buscar' => 'DEP-2026-001',
            'formato' => 'pdf',
        ]));
        $byDeposit->assertOk()
            ->assertViewIs('pagos.pago.pdf')
            ->assertSee('DEP-2026-001')
            ->assertDontSee('250.00');
        $this->assertCount(1, $byDeposit->viewData('pagosData'));

        $excel = $this->get(route('pagos.index', [
            'usuario' => 'CI-20260010',
            'buscar' => 'DEP-2026-001',
            'formato' => 'excel',
        ]));
        $excel->assertOk()->assertDownload();
    }

    private function createStudentPaymentFixture(): array
    {
        $student = Persona::create([
            'nombre' => 'María',
            'primerapellido' => 'Quispe',
            'segundoapellido' => 'Mamani',
            'ci' => 'CI-20260010',
            'email' => 'maria.quispe@example.test',
            'tipo' => 'estudiante',
        ]);
        User::create([
            'id_persona' => $student->id_persona,
            'usuario' => 'estudiante_20260010',
            'password' => Hash::make('password123'),
        ]);
        DB::table('estudiante')->insert([
            'id_persona' => $student->id_persona,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $actor = Persona::create([
            'nombre' => 'Admin',
            'primerapellido' => 'Prueba',
            'ci' => 'CI-ADMIN-PAGOS',
            'tipo' => 'admin',
        ]);
        User::create([
            'id_persona' => $actor->id_persona,
            'usuario' => 'admin_pagos',
            'password' => Hash::make('password123'),
        ]);

        $instituto = Instituto::create(['nombreinstituto' => 'Instituto de Prueba']);
        $carrera = Carrera::create([
            'codigo' => 'SIS-PAGOS-TEST',
            'nombre' => 'Sistemas',
            'id_instituto' => $instituto->id_instituto,
        ]);
        $gestion = GestionAcademica::create([
            'gestion' => 2026,
            'periodo' => 1,
            'regimen' => 1,
            'vigente' => true,
        ]);
        $inscritoId = DB::table('inscrito')->insertGetId([
            'codigo' => 20260010,
            'fecha' => '2026-03-10',
            'id_carrera' => $carrera->id_carrera,
            'id_persona' => $student->id_persona,
            'id_gestionacademica' => $gestion->id_gestionacademica,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $secondCareer = Carrera::create([
            'codigo' => 'ADM-PAGOS-TEST',
            'nombre' => 'Administración',
            'id_instituto' => $instituto->id_instituto,
        ]);
        $secondInscritoId = DB::table('inscrito')->insertGetId([
            'codigo' => 20260011,
            'fecha' => '2026-03-11',
            'deposito' => 'DEP-2026-002',
            'id_carrera' => $secondCareer->id_carrera,
            'id_persona' => $student->id_persona,
            'id_gestionacademica' => $gestion->id_gestionacademica,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $paymentWithReceipt = DB::table('pago')->insertGetId([
            'monto' => 100,
            'pagado' => true,
            'estado' => true,
            'id_inscrito' => $inscritoId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $paymentWithoutReceipt = DB::table('pago')->insertGetId([
            'monto' => 250,
            'pagado' => true,
            'estado' => true,
            'id_inscrito' => $secondInscritoId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('comprobante')->insert([
            'deposito' => 'DEP-2026-001',
            'fecha' => '2026-03-10 10:00:00',
            'valido' => true,
            'id_inscrito' => $inscritoId,
            'id_usuario' => $actor->id_persona,
            'id_estudiante' => $student->id_persona,
            'id_carrera' => $carrera->id_carrera,
            'id_gestionacademica' => $gestion->id_gestionacademica,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('detalle')->insert([
            [
                'nombre' => 'Matrícula',
                'codigo' => 'DEP-2026-001',
                'monto' => 100,
                'id_pago' => $paymentWithReceipt,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'nombre' => 'Beca',
                'codigo' => 'DEP-2026-002',
                'monto' => 250,
                'id_pago' => $paymentWithoutReceipt,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        return [
            'payment_with_receipt' => $paymentWithReceipt,
            'payment_without_receipt' => $paymentWithoutReceipt,
        ];
    }
}
