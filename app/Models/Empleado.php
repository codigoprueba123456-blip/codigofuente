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
     * Restringe una consulta a empleados habilitados.
     * Se usa para seleccionar responsables y docentes sin excluir registros históricos de otras consultas.
     */
    public function scopeActivos($query)
    {
        return $query->where('activo', true);
    }

    /**
     * Relaciona el registro laboral con la persona que conserva identidad y datos de contacto.
     */
    public function persona()
    {
        return $this->belongsTo(Persona::class, 'id_persona', 'id_persona');
    }

    /**
     * Devuelve los documentos asociados a la persona empleada.
     * Cada fila guarda la categoría del documento y su ruta en el almacenamiento público.
     */
    public function documentos(): HasMany
    {
        return $this->hasMany(EmpleadoDocumento::class, 'id_persona', 'id_persona');
    }
}
