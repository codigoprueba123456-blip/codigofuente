<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Crea los roles, los recursos autorizados y las cuentas asociadas a personas.
     */
    public function up(): void
    {
        // Nombre único para identificar cada tipo de acceso.
        Schema::create('rol', function (Blueprint $table) {
            $table->id('id_rol');
            $table->string('nombre')->unique();
            $table->timestamps();
        });

        // Cada recurso representa una ruta que puede protegerse por permisos.
        Schema::create('recurso', function (Blueprint $table) {
            $table->id('id_recurso');
            $table->string('nombre');
            $table->string('urlpattern');
            $table->timestamps();
        });

        // Une roles y recursos; la clave compuesta evita repetir el mismo permiso.
        Schema::create('privilegio', function (Blueprint $table) {
            $table->unsignedBigInteger('id_recurso');
            $table->unsignedBigInteger('id_rol');
            $table->timestamps();

            $table->primary(['id_recurso', 'id_rol']);

            // Al eliminar un rol o recurso, también se eliminan sus asociaciones de permisos.
            $table->foreign('id_recurso')->references('id_recurso')->on('recurso')->onDelete('cascade');
            $table->foreign('id_rol')->references('id_rol')->on('rol')->onDelete('cascade');
        });

        // Permite que una persona tenga una cuenta distinta para cada rol.
        Schema::create('cuenta', function (Blueprint $table) {
            $table->unsignedBigInteger('id_persona');
            $table->unsignedBigInteger('id_rol');
            $table->string('usuario')->unique();
            $table->string('password');
            $table->rememberToken();
            $table->timestamps();

            $table->primary(['id_persona', 'id_rol']);

            // Las cuentas dependen de la persona y del rol asignado.
            $table->foreign('id_persona')->references('id_persona')->on('personas')->onDelete('cascade');
            $table->foreign('id_rol')->references('id_rol')->on('rol')->onDelete('cascade');
        });
    }

    /**
     * Elimina primero las asociaciones y luego las tablas que las definen.
     */
    public function down(): void
    {
        Schema::dropIfExists('cuenta');
        Schema::dropIfExists('privilegio');
        Schema::dropIfExists('recurso');
        Schema::dropIfExists('rol');
    }
};