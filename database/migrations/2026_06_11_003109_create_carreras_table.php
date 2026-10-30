<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('carreras', function (Blueprint $table) {
            $table->id('id_carrera');
            $table->string('codigo')->unique(); 
            $table->integer('creditajeadmision')->nullable();
            $table->integer('creditajematricula')->nullable();
            $table->integer('nivelacademico')->nullable();
            $table->string('nombre');
            $table->integer('regimen')->nullable();
            $table->string('resolucionministerial1')->nullable();
            $table->string('resolucionministerial2')->nullable();
            $table->string('resolucionministerial3')->nullable();
            $table->integer('version')->nullable();
            $table->unsignedBigInteger('id_instituto');
            $table->unsignedBigInteger('id_jefecarrera')->nullable();
            $table->timestamps();

            $table->foreign('id_instituto')->references('id_instituto')->on('instituto')->onDelete('cascade');
            $table->foreign('id_jefecarrera')->references('id_persona')->on('empleados')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('carreras');
    }
};
