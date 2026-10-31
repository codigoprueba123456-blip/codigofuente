<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('recurso')
            ->where('urlpattern', 'planes/horarios')
            ->update([
                'nombre' => 'Asignación docente',
                'urlpattern' => 'planes/asignacion-docente',
                'updated_at' => now(),
            ]);
    }

    public function down(): void
    {
        DB::table('recurso')
            ->where('urlpattern', 'planes/asignacion-docente')
            ->update([
                'nombre' => 'Horarios',
                'urlpattern' => 'planes/horarios',
                'updated_at' => now(),
            ]);
    }
};
