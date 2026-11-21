<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Crea las cuentas de acceso y las tablas de recuperación de contraseña y sesión.
     */
    public function up(): void
    {
        // La clave de la cuenta coincide con la persona; aquí se guardan usuario y contraseña con hash.
        Schema::create('users', function (Blueprint $table) {
            // La cuenta reutiliza el identificador de personas en vez de generar otro.
            $table->unsignedBigInteger('id_persona')->primary(); 
            $table->string('usuario')->unique();
            $table->string('password');
            $table->rememberToken();
            $table->timestamps();

            // Al eliminar la persona también se elimina su cuenta de acceso.
            $table->foreign('id_persona')
                  ->references('id_persona')
                  ->on('personas')
                  ->onDelete('cascade');
        });

        // Guarda los tokens que permiten restablecer una contraseña.
        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('usuario')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

        // Almacena las sesiones y permite asociarlas al identificador de la persona.
        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->unsignedBigInteger('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });
    }

    /**
     * Elimina las sesiones, los tokens y las cuentas creadas por esta migración.
     */
    public function down(): void
    {
        Schema::dropIfExists('sessions');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('users');
    }
};