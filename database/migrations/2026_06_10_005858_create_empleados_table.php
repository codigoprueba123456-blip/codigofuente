<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('empleados', function (Blueprint $table) {
            $table->id('id_persona');
            $table->string('abreviaturaprofesion')->nullable();
            $table->string('codigo')->nullable();
            $table->timestamps();

            // Relación uno a uno / herencia con persona
            $table->foreign('id_persona')->references('id_persona')->on('personas')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('empleados');
    }
};
