<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('empleado_documentos', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('id_persona');
            $table->unsignedTinyInteger('slot');
            $table->string('ruta');
            $table->timestamps();

            $table->foreign('id_persona')
                ->references('id_persona')
                ->on('empleados')
                ->onDelete('cascade');
            $table->unique(['id_persona', 'slot']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('empleado_documentos');
    }
};
