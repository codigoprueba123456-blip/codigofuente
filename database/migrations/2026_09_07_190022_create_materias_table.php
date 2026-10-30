<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('materia', function (Blueprint $table) {
            $table->id('id_materia');
            $table->string('codigo', 255)->nullable();
            $table->string('nombre', 255);
            $table->integer('creditajemateria')->nullable();
            $table->boolean('curricular')->default(true);
            $table->integer('horas')->nullable();
            $table->integer('nivel')->nullable();
            $table->integer('numero')->nullable();

            // Claves Foráneas
            $table->unsignedBigInteger('id_carrera');
            $table->foreign('id_carrera')
                  ->references('id_carrera')
                ->on('carreras')
                  ->onDelete('cascade');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('materia');
    }
};