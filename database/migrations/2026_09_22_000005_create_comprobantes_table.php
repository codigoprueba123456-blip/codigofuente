<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('comprobante', function (Blueprint $table) {
            $table->id('id_comprobante');
            $table->unsignedBigInteger('codigo')->nullable()->unique();
            $table->string('deposito')->nullable();
            $table->timestamp('fecha')->nullable();
            $table->boolean('valido')->default(false);
            $table->unsignedBigInteger('id_inscrito')->nullable();
            $table->unsignedBigInteger('id_postulante')->nullable();
            $table->unsignedBigInteger('id_usuario')->nullable();
            $table->unsignedBigInteger('id_estudiante')->nullable();
            $table->unsignedBigInteger('id_carrera')->nullable();
            $table->unsignedBigInteger('id_gestionacademica')->nullable();
            $table->timestamps();

            $table->foreign('id_inscrito')->references('id_inscrito')->on('inscrito')->onDelete('set null');
            $table->foreign('id_postulante')->references('id_postulante')->on('postulante')->onDelete('set null');
            $table->foreign('id_usuario')->references('id_persona')->on('users')->onDelete('set null');
            $table->foreign('id_estudiante')->references('id_persona')->on('estudiante')->onDelete('set null');
            $table->foreign('id_carrera')->references('id_carrera')->on('carreras')->onDelete('set null');
            $table->foreign('id_gestionacademica')->references('id_gestionacademica')->on('gestionacademica')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('comprobante');
    }
};