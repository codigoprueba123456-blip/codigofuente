<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('postulante', function (Blueprint $table) {
            $table->id('id_postulante'); // PK
            
            // Datos del postulante con ÍNDICES (.index()) para velocidad de búsqueda
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

            // Datos de contacto y colegio
            $table->string('nombrecolegio');
            $table->string('caractercolegio');
            $table->integer('egresocolegio');
            $table->boolean('diplomabachiller')->default(false);
            $table->string('nombrecontacto');
            $table->string('parentescocontacto');
            $table->string('celularcontacto');
            $table->string('deposito');

            // -------------------------------------------------------------
            // DOCUMENTOS DIGITALES (Rutas de archivos en el servidor)
            // -------------------------------------------------------------
            $table->string('path_ci_frontal'); // Anverso del carnet de identidad
            $table->string('path_ci_reverso'); // Reverso del carnet de identidad
            $table->string('path_nacimiento'); // Certificado de Nacimiento
            $table->string('path_diploma');    // Title/Diploma de Bachiller

            // Llaves foráneas con relaciones exactas
            $table->unsignedBigInteger('id_carrera');
            $table->foreign('id_carrera')->references('id_carrera')->on('carreras')->onDelete('cascade');

            $table->unsignedBigInteger('id_gestionacademica');
            $table->foreign('id_gestionacademica')->references('id_gestionacademica')->on('gestionacademica')->onDelete('cascade');

            $table->unique(['deposito', 'id_gestionacademica'], 'unique_voucher_gestion');
            $table->unique(['ci', 'id_carrera', 'id_gestionacademica'], 'uq_postulante_ci_carrera_gestion');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('postulante');
    }
};