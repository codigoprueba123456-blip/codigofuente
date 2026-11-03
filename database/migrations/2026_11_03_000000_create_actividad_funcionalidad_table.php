<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('actividad_funcionalidad', function (Blueprint $table) {
            $table->unsignedBigInteger('id_actividad');
            $table->unsignedBigInteger('id_recurso');
            $table->primary(['id_actividad', 'id_recurso']);
            $table->foreign('id_actividad')->references('id_actividad')->on('actividad')->onDelete('cascade');
            $table->foreign('id_recurso')->references('id_recurso')->on('recurso')->onDelete('cascade');
        });

        DB::table('actividad')
            ->whereNotNull('funcionalidad')
            ->orderBy('id_actividad')
            ->each(function (object $actividad): void {
                if (DB::table('recurso')->where('id_recurso', $actividad->funcionalidad)->exists()) {
                    DB::table('actividad_funcionalidad')->insertOrIgnore([
                        'id_actividad' => $actividad->id_actividad,
                        'id_recurso' => $actividad->funcionalidad,
                    ]);
                }
            });
    }

    public function down(): void
    {
        Schema::dropIfExists('actividad_funcionalidad');
    }
};
