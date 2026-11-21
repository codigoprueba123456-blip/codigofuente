<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Crea las postulaciones con datos personales, escolares, documentos y carrera solicitada.
     */
    public function up(): void
    {
        Schema::create('postulante', function (Blueprint $table) {
            $table->id('id_postulante');
            
            // Los índices ayudan a localizar postulaciones por CI y nombres.
            $table->string('codigo')->unique()->nullable();
            $table->string('ci', 30)->index();
            $table->string('primerapellido', 100)->index();
            $table->string('segundoapellido', 100)->nullable()->index();
            $table->string('nombre', 100)->index();
            
            $table->string('celular', 20)->index();
            $table->string('telefono', 20)->nullable();
            $table->string('email');
            $table->string('direccion');
            $table->date('fechanacimiento');
            $table->string('lugarnacimiento');
            $table->string('lugarexpedicion')->nullable();
            $table->string('nacionalidad');
            $table->string('sexo');
            $table->string('foto')->nullable();
            $table->timestamp('fecha')->useCurrent();

            // Información escolar y contacto de emergencia declarados en la postulación.
            $table->string('nombrecolegio');
            $table->string('caractercolegio');
            $table->integer('egresocolegio');
            $table->boolean('diplomabachiller')->default(false);
            $table->string('nombrecontacto');
            $table->string('parentescocontacto');
            $table->string('celularcontacto');
            $table->string('deposito');

            // Rutas de los documentos guardados en el almacenamiento del sistema.
            $table->string('path_ci_frontal'); // Anverso del carnet de identidad.
            $table->string('path_ci_reverso'); // Reverso del carnet de identidad.
            $table->string('path_nacimiento'); // Certificado de nacimiento.
            $table->string('path_diploma');    // Diploma de bachiller.

            // Cada postulación corresponde a una carrera y gestión académica.
            $table->unsignedBigInteger('id_carrera');
            $table->foreign('id_carrera')->references('id_carrera')->on('carreras')->onDelete('cascade');

            $table->unsignedBigInteger('id_gestionacademica');
            $table->foreign('id_gestionacademica')->references('id_gestionacademica')->on('gestionacademica')->onDelete('cascade');

            $table->unique(['deposito', 'id_gestionacademica'], 'unique_voucher_gestion');
            $table->unique(['ci', 'id_carrera', 'id_gestionacademica'], 'uq_postulante_ci_carrera_gestion');

            $table->timestamps();
        });
    }

    /**
     * Elimina la tabla de postulaciones al revertir esta migración.
     */
    public function down(): void
    {
        Schema::dropIfExists('postulante');
    }
};