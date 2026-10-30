<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
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

            $table->foreign('id_comprobante')->references('id_comprobante')->on('comprobante')->onDelete('cascade');
            $table->foreign('id_pago')->references('id_pago')->on('pago')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('detalle');
    }
};