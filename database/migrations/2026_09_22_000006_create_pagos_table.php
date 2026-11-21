<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Crea la tabla que vincula pagos con postulaciones o inscripciones.
     * El módulo de pagos funciona como consulta de registros existentes; esta migración no define
     * una acción para agregar pagos desde ese módulo.
     */
    public function up(): void
    {
        Schema::create('pago', function (Blueprint $table) {
            $table->id('id_pago');
            $table->integer('concepto')->nullable();
            $table->decimal('monto', 12, 2)->default(0);
            $table->boolean('pagado')->default(false);
            $table->boolean('estado')->default(true);
            $table->unsignedBigInteger('id_inscrito')->nullable();
            $table->unsignedBigInteger('id_postulante')->nullable();
            $table->timestamps();

            $table->foreign('id_inscrito')->references('id_inscrito')->on('inscrito')->onDelete('set null');
            $table->foreign('id_postulante')->references('id_postulante')->on('postulante')->onDelete('set null');
        });
    }

    /**
     * Elimina la tabla de pagos al revertir esta migración.
     */
    public function down(): void
    {
        Schema::dropIfExists('pago');
    }
};