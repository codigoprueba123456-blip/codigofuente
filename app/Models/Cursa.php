<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Cursa extends Model
{
    use HasFactory;

    protected $table = 'cursa';

    protected $fillable = [
        'id_persona',
        'id_carrera',
        'id_gestionacademica',
        'nivelinicio',
    ];

    /**
     * Relaciona el registro de cursado con el estudiante al que corresponde.
     */
    public function persona()
    {
        return $this->belongsTo(Persona::class, 'id_persona', 'id_persona');
    }

    /**
     * Relaciona el registro con la carrera y permite consultar el nivel en que inició.
     */
    public function carrera()
    {
        return $this->belongsTo(Carrera::class, 'id_carrera', 'id_carrera');
    }
}