<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Crea los grupos que se ofrecen para una materia dentro de una gestión.
     */
    public function up(): void
    {
        Schema::create('grupo', function (Blueprint $table) {
            $table->id('id_grupo');
            $table->string('codigo', 255);
            // Estos valores permiten mostrar turno, controlar cupos y cerrar un grupo.
            $table->integer('capacidad')->nullable();
            $table->integer('turno')->nullable();
            $table->boolean('abierto')->default(true);

            // El grupo puede tener un docente y siempre pertenece a una gestión académica.
            $table->unsignedBigInteger('id_persona')->nullable();
            $table->foreign('id_persona')
                  ->references('id_persona')
                  ->on('personas')
                  ->onDelete('cascade');

            $table->unsignedBigInteger('id_gestionacademica');
            $table->foreign('id_gestionacademica')
                  ->references('id_gestionacademica')
                  ->on('gestionacademica')
                  ->onDelete('cascade');

            $table->unsignedBigInteger('id_materia')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Elimina los grupos creados por esta migración.
     */
    public function down(): void
    {
        Schema::dropIfExists('grupo');
    }
};