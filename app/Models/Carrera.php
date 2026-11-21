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
     * Restringe una consulta a carreras con activo=true.
     * Los controladores usan este scope para excluirlas de selectores de nuevas operaciones.
     */
    public function scopeActivas($query)
    {
        return $query->where('activo', true);
    }

    /**
     * Devuelve el mapa régimen => nivel => etiqueta que usa la interfaz académica.
     * El régimen anual contiene tres años y el semestral seis semestres; mantener este mapa centralizado
     * evita que formularios, validaciones y etiquetas ofrezcan límites distintos.
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
     * Devuelve las claves de nivel permitidas para el régimen recibido.
     * Primero normaliza el régimen y devuelve una lista vacía si el valor no corresponde a uno conocido.
     */
    public static function nivelesPorRegimen(int|string|null $regimen): array
    {
        $regimen = self::regimenEfectivo($regimen);

        return array_keys(self::etiquetasNivelesPorRegimen()[$regimen] ?? []);
    }

    /**
     * Busca la etiqueta del nivel según el régimen y conserva el valor recibido como texto si no hay etiqueta.
     * El fallback permite presentar niveles históricos o incompletos sin provocar errores de acceso al mapa.
     */
    public static function etiquetaNivel(int|string|null $regimen, int|string|null $nivel): string
    {
        $regimen = self::regimenEfectivo($regimen);

        return self::etiquetasNivelesPorRegimen()[$regimen][(int) $nivel]
            ?? (string) $nivel;
    }

    /**
     * Convierte entradas de formulario o base de datos a un régimen anual/semestral válido.
     * Los valores nulos o vacíos mantienen compatibilidad asumiendo anual; un número desconocido devuelve null.
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
     * Normaliza las tres resoluciones: omite vacías y quita prefijos como R.M. o N°.
     * Devuelve solo los identificadores limpios, listos para mostrarse en tablas y etiquetas.
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
     * Formatea el nombre de este registro con sus resoluciones ya normalizadas.
     * Delegar al formateador estático mantiene el mismo resultado para modelos persistidos y datos sueltos.
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
     * Compone una etiqueta desde valores recibidos, sin consultar ni guardar una carrera.
     * Agrega el prefijo R.M. y une resoluciones disponibles; si no hay ninguna, devuelve solo el nombre.
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
     * Relaciona la carrera con su instituto propietario para obtener, entre otros datos, precio por crédito.
     */
    public function instituto()
    {
        return $this->belongsTo(Instituto::class, 'id_instituto', 'id_instituto');
    }

    /**
     * Relaciona la clave id_jefecarrera con el registro laboral de la persona responsable.
     */
    public function jefeCarrera()
    {
        return $this->belongsTo(Empleado::class, 'id_jefecarrera', 'id_persona');
    }

    /**
     * Relaciona las tarifas de matrícula de esta carrera a través de todas las gestiones.
     * Para calcular un cobro debe filtrarse además por gestión y nivel en el servicio de tarifas.
     */
    public function tarifasMatricula()
    {
        return $this->hasMany(TarifaMatricula::class, 'id_carrera', 'id_carrera');
    }
}
