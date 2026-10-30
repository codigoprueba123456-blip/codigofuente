<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('grupo', function (Blueprint $table) {
            $table->id('id_grupo');
            $table->string('codigo', 255);
            $table->integer('capacidad')->nullable();
            $table->integer('turno')->nullable();
            $table->boolean('abierto')->default(true);

            // Claves Foráneas (FK)
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

    public function down(): void
    {
        Schema::dropIfExists('grupo');
    }
};