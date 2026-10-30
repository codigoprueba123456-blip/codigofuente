<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Carrera extends Model
{
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

    public function instituto()
    {
        return $this->belongsTo(Instituto::class, 'id_instituto', 'id_instituto');
    }

    public function jefeCarrera()
    {
        return $this->belongsTo(Empleado::class, 'id_jefecarrera', 'id_persona');
    }
}
