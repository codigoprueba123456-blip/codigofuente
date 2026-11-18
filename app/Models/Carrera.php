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
    protected $fillable = [ 'codigo', 'creditajeadmision', 'creditajematricula', 'creditajeadmision_manual', 'creditajematricula_manual', 'nivelacademico', 'nombre', 'activo', 'regimen', 'resolucionministerial1', 'resolucionministerial2',
            'resolucionministerial3', 'version', 'id_instituto', 'id_jefecarrera'
    ];

    protected $casts = [
        'activo' => 'boolean',
    ];

    /**
     * Filtra carreras habilitadas para selección u operaciones académicas.
     */
    public function scopeActivas($query)
    {
        return $query->where('activo', true);
    }

    /**
     * Devuelve las etiquetas de niveles válidas para los regímenes anual y semestral.
     */
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

    /**
     * Obtiene los números de nivel que corresponden al régimen recibido.
     */
    public static function nivelesPorRegimen(int|string|null $regimen): array
    {
        $regimen = self::regimenEfectivo($regimen);

        return array_keys(self::etiquetasNivelesPorRegimen()[$regimen] ?? []);
    }

    /**
     * Devuelve la etiqueta del nivel o el valor original si no hay una etiqueta definida.
     */
    public static function etiquetaNivel(int|string|null $regimen, int|string|null $nivel): string
    {
        $regimen = self::regimenEfectivo($regimen);

        return self::etiquetasNivelesPorRegimen()[$regimen][(int) $nivel]
            ?? (string) $nivel;
    }

    /**
     * Convierte el régimen recibido a una constante válida; si está vacío, asume el régimen anual.
     */
    public static function regimenEfectivo(int|string|null $regimen): ?int
    {
        if ($regimen === null || $regimen === '') {
            return self::REGIMEN_ANUAL;
        }

        $regimen = (int) $regimen;

        return array_key_exists($regimen, self::etiquetasNivelesPorRegimen()) ? $regimen : null;
    }

    /**
     * Limpia prefijos repetidos de las resoluciones y devuelve solo los números registrados.
     */
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

    /**
     * Formatea el nombre de esta carrera junto con sus resoluciones ministeriales.
     */
    public function nombreConResolucionMinisterial(): string
    {
        return self::formatearNombreConResolucion(
            $this->nombre,
            $this->resolucionministerial1,
            $this->resolucionministerial2,
            $this->resolucionministerial3
        );
    }

    /**
     * Formatea un nombre de carrera y hasta tres resoluciones sin depender de un registro persistido.
     */
    public static function formatearNombreConResolucion(
        string $nombre,
        ?string $resolucion1,
        ?string $resolucion2,
        ?string $resolucion3
    ): string {
        $carrera = new self([
            'nombre' => $nombre,
            'resolucionministerial1' => $resolucion1,
            'resolucionministerial2' => $resolucion2,
            'resolucionministerial3' => $resolucion3,
        ]);
        $resoluciones = $carrera->resolucionesMinisteriales();

        return $nombre.($resoluciones
            ? ' · R.M. '.implode(' / ', $resoluciones)
            : '');
    }

    /**
     * Obtiene el instituto al que está asociada la carrera.
     */
    public function instituto()
    {
        return $this->belongsTo(Instituto::class, 'id_instituto', 'id_instituto');
    }

    /**
     * Obtiene al empleado asignado como jefe de carrera.
     */
    public function jefeCarrera()
    {
        return $this->belongsTo(Empleado::class, 'id_jefecarrera', 'id_persona');
    }
}
