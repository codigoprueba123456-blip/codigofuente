<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Empleado extends Model
{
    protected $table = 'empleados';
    protected $primaryKey = 'id_persona';
    public $incrementing = false; // Para indicar que la clave primaria no es auto-incremental
    protected $fillable = ['id_persona', 'abreviaturaprofesion', 'codigo'];

    // Relación con Persona (uno a uno)
    public function persona()
    {
        return $this->belongsTo(Persona::class, 'id_persona', 'id_persona'); // Relación con Persona usando id_persona como clave foránea
    }

    public function documentos(): HasMany
    {
        return $this->hasMany(EmpleadoDocumento::class, 'id_persona', 'id_persona');
    }
}
