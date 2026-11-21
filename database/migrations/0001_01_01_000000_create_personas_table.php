<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Crea la ficha de identidad que comparten estudiantes, postulantes y usuarios.
     */
    public function up(): void
    {
        Schema::create('personas', function (Blueprint $table) {
            $table->id('id_persona');
            // Datos personales y de contacto; el CI identifica de forma única a cada persona.
            $table->string('tipo',31)->nullable();
            $table->integer('celular')->nullable();
            $table->string('direccion')->nullable();
            $table->string('ci')->unique();
            $table->string('email')->nullable();
            $table->date('fechanacimiento')->nullable();
            $table->string('foto')->nullable();
            $table->string('lugarexpedicion')->nullable();
            $table->string('lugarnacimiento')->nullable();
            $table->string('nacionalidad')->nullable();
            $table->string('nombre');
            $table->string('primerapellido');
            $table->string('segundoapellido')->nullable();
            $table->string('sexo')->nullable();
            $table->integer('telefono')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Elimina la tabla de personas al revertir esta migración.
     */
    public function down(): void
    {
        Schema::dropIfExists('personas');
    }
};
