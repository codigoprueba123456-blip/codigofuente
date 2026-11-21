<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Representa una inscripción de una persona en una carrera y gestión académica.
 * El estado y la beca se convierten a valores booleanos al leerlos desde la base de datos.
 */
class Inscrito extends Model
{
    protected $table = 'inscrito';
    protected $primaryKey = 'id_inscrito';

    protected $guarded = [];

    protected $casts = [
        'activo' => 'boolean',
        'becado' => 'boolean',
    ];
}
