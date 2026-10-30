<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('gestionacademica', function (Blueprint $table) {
            $table->id('id_gestionacademica');
            $table->integer('gestion');
            $table->integer('periodo');
            $table->integer('regimen');
            $table->date('inicio')->nullable();
            $table->date('fin')->nullable();
            $table->boolean('vigente')->default(true);
            $table->integer('modalidadevaluacion')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('gestionacademica');
    }
};