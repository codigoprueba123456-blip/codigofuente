<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Empleado extends Model
{
    protected $table = 'empleados';
    protected $primaryKey = 'id_persona';
    public $incrementing = false; // Para indicar que la clave primaria no es auto-incremental
    protected $fillable = ['id_persona', 'abreviaturaprofesion', 'codigo', 'activo'];

    protected $casts = ['activo' => 'boolean'];

    /**
     * Filtra los registros laborales que están habilitados.
     */
    public function scopeActivos($query)
    {
        return $query->where('activo', true);
    }

    /**
     * Obtiene los datos personales vinculados al registro laboral.
     */
    public function persona()
    {
        return $this->belongsTo(Persona::class, 'id_persona', 'id_persona');
    }

    /**
     * Obtiene los documentos adjuntos al expediente de este empleado.
     */
    public function documentos(): HasMany
    {
        return $this->hasMany(EmpleadoDocumento::class, 'id_persona', 'id_persona');
    }
}
