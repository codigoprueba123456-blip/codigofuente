<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Crea los conceptos que detallan el monto de un pago o comprobante.
     */
    public function up(): void
    {
        Schema::create('detalle', function (Blueprint $table) {
            $table->id('id_detalle');
            $table->integer('concepto')->nullable();
            $table->string('codigo')->nullable();
            $table->string('nombre')->nullable();
            $table->decimal('monto', 12, 2)->default(0);
            $table->unsignedBigInteger('id_comprobante')->nullable();
            $table->unsignedBigInteger('id_pago')->nullable();
            $table->timestamps();

            // El detalle se elimina si desaparece el comprobante o el pago al que pertenece.
            $table->foreign('id_comprobante')->references('id_comprobante')->on('comprobante')->onDelete('cascade');
            $table->foreign('id_pago')->references('id_pago')->on('pago')->onDelete('cascade');
        });
    }

    /**
     * Elimina los detalles al revertir esta migración.
     */
    public function down(): void
    {
        Schema::dropIfExists('detalle');
    }
};