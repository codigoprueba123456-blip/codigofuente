<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('actividad', function (Blueprint $table) {
            $table->id('id_actividad');
            $table->string('nombre', 255);
            $table->string('descripcion', 255)->nullable();
            $table->timestamp('inicio')->nullable();
            $table->timestamp('fin')->nullable();
            $table->integer('funcionalidad')->nullable();

            // Relación con gestión académica según conector del diagrama
            $table->unsignedBigInteger('id_gestionacademica')->nullable();
            $table->foreign('id_gestionacademica')
                  ->references('id_gestionacademica')
                  ->on('gestionacademica')
                  ->onDelete('cascade');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('actividad');
    }
};