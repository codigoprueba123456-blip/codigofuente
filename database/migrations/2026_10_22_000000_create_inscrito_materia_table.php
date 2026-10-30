<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inscrito_materia', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('id_inscrito');
            $table->unsignedBigInteger('id_materia');
            $table->unsignedBigInteger('id_grupo')->nullable();
            $table->timestamps();

            $table->foreign('id_inscrito')->references('id_inscrito')->on('inscrito')->cascadeOnDelete();
            $table->foreign('id_materia')->references('id_materia')->on('materia')->cascadeOnDelete();
            $table->foreign('id_grupo')->references('id_grupo')->on('grupo')->nullOnDelete();
            $table->unique(['id_inscrito', 'id_materia']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inscrito_materia');
    }
};
