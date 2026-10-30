<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Tabla rol
        Schema::create('rol', function (Blueprint $table) {
            $table->id('id_rol');
            $table->string('nombre')->unique();
            $table->timestamps();
        });

        // Tabla recurso
        Schema::create('recurso', function (Blueprint $table) {
            $table->id('id_recurso');
            $table->string('nombre');
            $table->string('urlpattern');
            $table->timestamps();
        });

        // Tabla privilegio (Relación muchos a muchos entre Rol y Recurso)
        Schema::create('privilegio', function (Blueprint $table) {
            $table->unsignedBigInteger('id_recurso');
            $table->unsignedBigInteger('id_rol');
            $table->timestamps();

            // Clave primaria compuesta
            $table->primary(['id_recurso', 'id_rol']);

            // Relaciones
            $table->foreign('id_recurso')->references('id_recurso')->on('recurso')->onDelete('cascade');
            $table->foreign('id_rol')->references('id_rol')->on('rol')->onDelete('cascade');
        });

        // Tabla cuenta
        Schema::create('cuenta', function (Blueprint $table) {
            $table->unsignedBigInteger('id_persona');
            $table->unsignedBigInteger('id_rol');
            $table->string('usuario')->unique();
            $table->string('password');
            $table->rememberToken();
            $table->timestamps();

            // Clave primaria compuesta
            $table->primary(['id_persona', 'id_rol']);

            // Relaciones
            $table->foreign('id_persona')->references('id_persona')->on('personas')->onDelete('cascade');
            $table->foreign('id_rol')->references('id_rol')->on('rol')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cuenta');
        Schema::dropIfExists('privilegio');
        Schema::dropIfExists('recurso');
        Schema::dropIfExists('rol');
    }
};