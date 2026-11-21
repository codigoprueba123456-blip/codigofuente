<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // Documentos obligatorios al postular, pero que pueden faltar en registros históricos o en flujos posteriores.
    private const DOCUMENT_COLUMNS = [
        'path_ci_frontal',
        'path_ci_reverso',
        'path_nacimiento',
        'path_diploma',
    ];

    /**
     * Permite que las rutas de documentos de una postulación queden vacías.
     */
    public function up(): void
    {
        Schema::table('postulante', function (Blueprint $table): void {
            foreach (self::DOCUMENT_COLUMNS as $column) {
                $table->string($column)->nullable()->change();
            }
        });
    }

    /**
     * Repone una cadena vacía antes de volver a exigir un valor en cada documento.
     */
    public function down(): void
    {
        // Prepara los registros sin documento para que cumplan la restricción al revertir el cambio.
        foreach (self::DOCUMENT_COLUMNS as $column) {
            DB::table('postulante')->whereNull($column)->update([$column => '']);
        }

        Schema::table('postulante', function (Blueprint $table): void {
            foreach (self::DOCUMENT_COLUMNS as $column) {
                $table->string($column)->nullable(false)->change();
            }
        });
    }
};
