<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const DOCUMENT_COLUMNS = [
        'path_ci_frontal',
        'path_ci_reverso',
        'path_nacimiento',
        'path_diploma',
    ];

    public function up(): void
    {
        Schema::table('postulante', function (Blueprint $table): void {
            foreach (self::DOCUMENT_COLUMNS as $column) {
                $table->string($column)->nullable()->change();
            }
        });
    }

    public function down(): void
    {
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
