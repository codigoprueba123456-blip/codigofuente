<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Representa la ficha académica asociada a una persona.
 * Usa id_persona como clave y no genera automáticamente ese identificador.
 */
class Estudiante extends Model
{
    protected $table = 'estudiante';
    protected $primaryKey = 'id_persona';
    public $incrementing = false;

    protected $guarded = [];
}
