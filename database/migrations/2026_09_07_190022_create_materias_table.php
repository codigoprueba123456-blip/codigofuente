<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Crea las materias y las vincula con la carrera a la que pertenecen.
     */
    public function up(): void
    {
        Schema::create('materia', function (Blueprint $table) {
            // La carrera, el nivel y el número permiten ordenar la oferta académica.
            $table->id('id_materia');
            $table->string('codigo', 255)->nullable();
            $table->string('nombre', 255);
            $table->integer('creditajemateria')->nullable();
            $table->boolean('curricular')->default(true);
            $table->integer('horas')->nullable();
            $table->integer('nivel')->nullable();
            $table->integer('numero')->nullable();

            // Cada materia forma parte de una carrera; se elimina si se elimina esa carrera.
            $table->unsignedBigInteger('id_carrera');
            $table->foreign('id_carrera')
                  ->references('id_carrera')
                ->on('carreras')
                  ->onDelete('cascade');

            $table->timestamps();
        });
    }

    /**
     * Elimina la tabla de materias al revertir esta migración.
     */
    public function down(): void
    {
        Schema::dropIfExists('materia');
    }
};