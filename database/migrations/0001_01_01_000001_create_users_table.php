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
        // 1. Tabla 'usuarios' personalizada
        Schema::create('users', function (Blueprint $table) {
            // Clave primaria basada en id_persona (no autoincremental propia)
            $table->unsignedBigInteger('id_persona')->primary(); 
            $table->string('usuario')->unique();
            $table->string('password');
            $table->rememberToken();
            $table->timestamps();

            // Clave foránea vinculada a la tabla personas
            $table->foreign('id_persona')
                  ->references('id_persona')
                  ->on('personas')
                  ->onDelete('cascade');
        });

        // 2. Tabla para tokens de restablecimiento
        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('usuario')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

        // 3. Tabla de sesiones adaptada a la clave id_persona
        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->unsignedBigInteger('user_id')->nullable()->index(); // Almacenará el id_persona
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sessions');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('users');
    }
};