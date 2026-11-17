<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('postulante', function (Blueprint $table): void {
            $table->unsignedTinyInteger('turno')->nullable();
            $table->string('paralelo', 20)->nullable();
            $table->index(
                ['id_carrera', 'id_gestionacademica', 'turno', 'paralelo'],
                'postulante_turno_paralelo_index'
            );
        });
    }

    public function down(): void
    {
        Schema::table('postulante', function (Blueprint $table): void {
            $table->dropIndex('postulante_turno_paralelo_index');
            $table->dropColumn(['turno', 'paralelo']);
        });
    }
};
