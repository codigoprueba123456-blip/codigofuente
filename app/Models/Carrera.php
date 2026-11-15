<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Carrera extends Model
{
    public const NIVEL_TECNICO_SUPERIOR = 1;
    public const NIVEL_TECNICO_MEDIO = 2;
    public const REGIMEN_ANUAL = 1;
    public const REGIMEN_SEMESTRAL = 2;

    protected $table = 'carreras';
    protected $primaryKey = 'id_carrera';
    protected $fillable = [ 'codigo', 'creditajeadmision','creditajematricula', 'nivelacademico', 'nombre', 'activo', 'regimen', 'resolucionministerial1', 'resolucionministerial2',
            'resolucionministerial3', 'version', 'id_instituto', 'id_jefecarrera'
    ];

    protected $casts = [
        'activo' => 'boolean',
    ];

    public function scopeActivas($query)
    {
        return $query->where('activo', true);
    }

    public static function etiquetasNivelesPorRegimen(): array
    {
        return [
            self::REGIMEN_ANUAL => [
                1 => 'Primer año',
                2 => 'Segundo año',
                3 => 'Tercer año',
            ],
            self::REGIMEN_SEMESTRAL => [
                1 => 'Primer semestre',
                2 => 'Segundo semestre',
                3 => 'Tercer semestre',
                4 => 'Cuarto semestre',
                5 => 'Quinto semestre',
                6 => 'Sexto semestre',
            ],
        ];
    }

    public static function nivelesPorRegimen(int|string|null $regimen): array
    {
        $regimen = self::regimenEfectivo($regimen);

        return array_keys(self::etiquetasNivelesPorRegimen()[$regimen] ?? []);
    }

    public static function etiquetaNivel(int|string|null $regimen, int|string|null $nivel): string
    {
        $regimen = self::regimenEfectivo($regimen);

        return self::etiquetasNivelesPorRegimen()[$regimen][(int) $nivel]
            ?? (string) $nivel;
    }

    public static function regimenEfectivo(int|string|null $regimen): ?int
    {
        if ($regimen === null || $regimen === '') {
            return self::REGIMEN_ANUAL;
        }

        $regimen = (int) $regimen;

        return array_key_exists($regimen, self::etiquetasNivelesPorRegimen()) ? $regimen : null;
    }

    public function resolucionesMinisteriales(): array
    {
        return array_values(array_filter(array_map(function (?string $resolucion): ?string {
            if ($resolucion === null || trim($resolucion) === '') {
                return null;
            }

            $resolucion = trim($resolucion);

            return trim(preg_replace('/^R\.?\s*M\.?\s*(?:N[º°]\s*)?/iu', '', $resolucion) ?? $resolucion);
        }, [
            $this->resolucionministerial1,
            $this->resolucionministerial2,
            $this->resolucionministerial3,
        ])));
    }

    public function instituto()
    {
        return $this->belongsTo(Instituto::class, 'id_instituto', 'id_instituto');
    }

    public function jefeCarrera()
    {
        return $this->belongsTo(Empleado::class, 'id_jefecarrera', 'id_persona');
    }
}
