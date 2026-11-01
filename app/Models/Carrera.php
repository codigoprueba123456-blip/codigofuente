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
