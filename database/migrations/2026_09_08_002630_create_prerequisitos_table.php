<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('prerequisito', function (Blueprint $table) {
            $table->unsignedBigInteger('id_materia');
            $table->unsignedBigInteger('id_prerequisito');

            // Clave Primaria Compuesta
            $table->primary(['id_materia', 'id_prerequisito']);

            // Relaciones autorreferenciales a la tabla materia
            $table->foreign('id_materia')
                  ->references('id_materia')
                  ->on('materia')
                  ->onDelete('cascade');

            $table->foreign('id_prerequisito')
                  ->references('id_materia')
                  ->on('materia')
                  ->onDelete('cascade');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('prerequisito');
    }
};