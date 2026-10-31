<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const TYPES = [
        1 => 'Carnet de identidad (CI)',
        2 => 'Título en provisión nacional',
        3 => 'Diplomado en educación superior',
        4 => 'Certificado de quechua',
    ];

    public function up(): void
    {
        if (! Schema::hasColumn('empleado_documentos', 'slot')) {
            return;
        }

        if (! Schema::hasColumn('empleado_documentos', 'tipo')) {
            Schema::table('empleado_documentos', function (Blueprint $table): void {
                $table->string('tipo')->nullable();
            });
        }

        foreach (self::TYPES as $slot => $type) {
            DB::table('empleado_documentos')
                ->where('slot', $slot)
                ->update(['tipo' => $type]);
        }

        DB::table('empleado_documentos')
            ->whereNull('tipo')
            ->get(['id', 'slot'])
            ->each(function (object $document): void {
                DB::table('empleado_documentos')
                    ->where('id', $document->id)
                    ->update(['tipo' => 'Documento ' . $document->slot]);
            });

        Schema::table('empleado_documentos', function (Blueprint $table): void {
            $table->index('id_persona', 'empleado_documentos_persona_index');
        });

        Schema::table('empleado_documentos', function (Blueprint $table): void {
            $table->dropUnique(['id_persona', 'slot']);
            $table->dropColumn('slot');
        });

        Schema::table('empleado_documentos', function (Blueprint $table): void {
            $table->unique(['id_persona', 'tipo'], 'empleado_documentos_persona_tipo_unique');
        });

        Schema::table('empleado_documentos', function (Blueprint $table): void {
            $table->dropIndex('empleado_documentos_persona_index');
        });
    }

    public function down(): void
    {
        if (! Schema::hasColumn('empleado_documentos', 'tipo')) {
            return;
        }

        Schema::table('empleado_documentos', function (Blueprint $table): void {
            $table->index('id_persona', 'empleado_documentos_persona_index');
        });

        Schema::table('empleado_documentos', function (Blueprint $table): void {
            $table->dropUnique('empleado_documentos_persona_tipo_unique');
            $table->unsignedTinyInteger('slot')->nullable();
        });

        foreach (self::TYPES as $slot => $type) {
            DB::table('empleado_documentos')
                ->where('tipo', $type)
                ->update(['slot' => $slot]);
        }

        Schema::table('empleado_documentos', function (Blueprint $table): void {
            $table->dropColumn('tipo');
        });

        Schema::table('empleado_documentos', function (Blueprint $table): void {
            $table->unique(['id_persona', 'slot']);
        });

        Schema::table('empleado_documentos', function (Blueprint $table): void {
            $table->dropIndex('empleado_documentos_persona_index');
        });
    }
};
