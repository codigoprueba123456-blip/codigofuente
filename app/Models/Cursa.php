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
     * Obtiene a la persona asociada al historial de cursado.
     */
    public function persona()
    {
        return $this->belongsTo(Persona::class, 'id_persona', 'id_persona');
    }

    /**
     * Obtiene la carrera en la que se registra el nivel de inicio.
     */
    public function carrera()
    {
        return $this->belongsTo(Carrera::class, 'id_carrera', 'id_carrera');
    }
}