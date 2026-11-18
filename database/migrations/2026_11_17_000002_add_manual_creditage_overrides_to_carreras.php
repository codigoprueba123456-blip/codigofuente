<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('carreras', function (Blueprint $table): void {
            $table->integer('creditajeadmision_manual')->nullable();
            $table->integer('creditajematricula_manual')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('carreras', function (Blueprint $table): void {
            $table->dropColumn(['creditajeadmision_manual', 'creditajematricula_manual']);
        });
    }
};
