<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::transaction(function (): void {
            $careers = DB::table('carreras as c')
                ->join('instituto as i', 'i.id_instituto', '=', 'c.id_instituto')
                ->select('c.id_carrera', 'i.preciocredito')
                ->orderBy('c.id_carrera')
                ->get();

            foreach ($careers as $career) {
                $total = (int) ($career->preciocredito ?? 0) * 10;
                DB::table('carreras')
                    ->where('id_carrera', $career->id_carrera)
                    ->update([
                        'creditajeadmision' => $total,
                        'creditajematricula' => $total,
                    ]);

                DB::table('materia')
                    ->where('id_carrera', $career->id_carrera)
                    ->where('curricular', false)
                    ->update(['creditajemateria' => null]);

                $subjects = DB::table('materia')
                    ->where('id_carrera', $career->id_carrera)
                    ->where('curricular', true)
                    ->orderBy('nivel')
                    ->orderBy('numero')
                    ->orderBy('id_materia')
                    ->pluck('id_materia');

                $count = $subjects->count();
                if ($count === 0) {
                    continue;
                }

                $base = intdiv($total, $count);
                $remainder = $total % $count;
                foreach ($subjects as $index => $subjectId) {
                    DB::table('materia')
                        ->where('id_materia', $subjectId)
                        ->update(['creditajemateria' => $base + ($index < $remainder ? 1 : 0)]);
                }
            }
        });
    }

    public function down(): void
    {
        // The previous manually entered credit values cannot be recovered.
    }
};
