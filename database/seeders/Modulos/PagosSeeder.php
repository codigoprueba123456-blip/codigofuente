<?php

namespace Database\Seeders\Modulos;

use App\Models\Postulante;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PagosSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(InscripcionesSeeder::class);

        $postulantes = Postulante::query()
            ->where('codigo', 'like', 'DEMO-POST-%')
            ->get();

        foreach ($postulantes as $postulante) {
            $now = now();
            $monto = (float) (DB::table('carreras as carrera')
                ->join('instituto', 'instituto.id_instituto', '=', 'carrera.id_instituto')
                ->where('carrera.id_carrera', $postulante->id_carrera)
                ->value(DB::raw(
                    'COALESCE(carrera.creditajematricula, 0) * COALESCE(instituto.preciocredito, 0)'
                )) ?? 0);

            DB::table('pago')->updateOrInsert(
                ['id_postulante' => $postulante->id_postulante],
                [
                    'concepto' => null,
                    'monto' => $monto,
                    'pagado' => false,
                    'estado' => true,
                    'updated_at' => $now,
                    'created_at' => $now,
                ]
            );

            $pago = DB::table('pago')
                ->where('id_postulante', $postulante->id_postulante)
                ->firstOrFail();

            DB::table('comprobante')->updateOrInsert(
                ['id_postulante' => $postulante->id_postulante],
                [
                    'deposito' => $postulante->deposito,
                    'fecha' => $postulante->fecha,
                    'valido' => false,
                    'id_carrera' => $postulante->id_carrera,
                    'id_gestionacademica' => $postulante->id_gestionacademica,
                    'updated_at' => $now,
                    'created_at' => $now,
                ]
            );

            DB::table('detalle')->updateOrInsert(
                [
                    'id_pago' => $pago->id_pago,
                    'codigo' => $postulante->codigo,
                ],
                [
                    'concepto' => null,
                    'nombre' => 'Inscripción de postulante',
                    'monto' => $monto,
                    'updated_at' => $now,
                    'created_at' => $now,
                ]
            );
        }
    }
}
