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
        Schema::create('instituto', function (Blueprint $table) {
            $table->id('id_instituto');
            $table->string('actividadeconomica')->nullable();
            $table->string('caracter')->nullable(); 
            $table->string('logo')->nullable();
            $table->string('nit')->nullable();
            $table->string('nombreimpuestos')->nullable();
            $table->string('nombreregulador')->nullable();
            $table->integer('preciocredito')->nullable();
            $table->string('resolucionministerial')->nullable();
            $table->string('ubicacion')->nullable();
            $table->string('abreviatura')->nullable();
            //$table->string('token')->nullable();
            $table->string('nombreinstituto');
            $table->integer('telefono')->nullable();
            $table->unsignedBigInteger('id_directoracademico')->nullable();
            $table->unsignedBigInteger('id_rector')->nullable();
            $table->timestamps();

            $table->foreign('id_directoracademico')->references('id_persona')->on('empleados')->onDelete('set null');
            $table->foreign('id_rector')->references('id_persona')->on('empleados')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('institutos');
    }
};
