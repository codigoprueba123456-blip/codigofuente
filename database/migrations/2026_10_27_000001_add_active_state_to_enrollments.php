<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('inscrito', 'activo')) {
            Schema::table('inscrito', function (Blueprint $table): void {
                $table->boolean('activo')->default(true)->after('becado');
            });
        }

        if (! Schema::hasIndex('inscrito', 'inscrito_persona_carrera_gestion_index')) {
            Schema::table('inscrito', function (Blueprint $table): void {
                $table->index(
                    ['id_persona', 'id_carrera', 'id_gestionacademica', 'activo'],
                    'inscrito_persona_carrera_gestion_index'
                );
            });
        }

        foreach ([
            'inscrito_voucher_gestion_unique',
            'inscrito_persona_carrera_gestion_unique',
            'inscrito_postulante_origen_unique',
        ] as $index) {
            if (Schema::hasIndex('inscrito', $index)) {
                Schema::table('inscrito', function (Blueprint $table) use ($index): void {
                    $table->dropUnique($index);
                });
            }
        }

        if (! Schema::hasIndex('inscrito', 'inscrito_voucher_gestion_index')) {
            Schema::table('inscrito', function (Blueprint $table): void {
                $table->index(['deposito', 'id_gestionacademica'], 'inscrito_voucher_gestion_index');
            });
        }

        if (! Schema::hasIndex('inscrito', 'inscrito_postulante_origen_index')) {
            Schema::table('inscrito', function (Blueprint $table): void {
                $table->index('id_postulante_origen', 'inscrito_postulante_origen_index');
            });
        }

        if (! Schema::hasColumn('comprobante', 'activo')) {
            Schema::table('comprobante', function (Blueprint $table): void {
                $table->boolean('activo')->default(true)->after('valido');
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasIndex('inscrito', 'inscrito_voucher_gestion_unique')) {
            Schema::table('inscrito', function (Blueprint $table): void {
                $table->unique(['deposito', 'id_gestionacademica'], 'inscrito_voucher_gestion_unique');
            });
        }

        if (! Schema::hasIndex('inscrito', 'inscrito_persona_carrera_gestion_unique')) {
            Schema::table('inscrito', function (Blueprint $table): void {
                $table->unique(
                    ['id_persona', 'id_carrera', 'id_gestionacademica'],
                    'inscrito_persona_carrera_gestion_unique'
                );
            });
        }

        if (! Schema::hasIndex('inscrito', 'inscrito_postulante_origen_unique')) {
            Schema::table('inscrito', function (Blueprint $table): void {
                $table->unique('id_postulante_origen', 'inscrito_postulante_origen_unique');
            });
        }

        foreach ([
            'inscrito_voucher_gestion_index',
            'inscrito_postulante_origen_index',
            'inscrito_persona_carrera_gestion_index',
        ] as $index) {
            if (Schema::hasIndex('inscrito', $index)) {
                Schema::table('inscrito', function (Blueprint $table) use ($index): void {
                    $table->dropIndex($index);
                });
            }
        }

        if (Schema::hasColumn('comprobante', 'activo')) {
            Schema::table('comprobante', function (Blueprint $table): void {
                $table->dropColumn('activo');
            });
        }

        if (Schema::hasColumn('inscrito', 'activo')) {
            Schema::table('inscrito', function (Blueprint $table): void {
                $table->dropColumn('activo');
            });
        }
    }
};
