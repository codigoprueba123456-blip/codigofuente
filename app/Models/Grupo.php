<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Grupo extends Model
{
    protected $table = 'grupo';
    protected $primaryKey = 'id_grupo';
    protected $fillable = [
        'codigo',
        'capacidad',
        'turno',
        'abierto',
        'id_persona',
        'id_gestionacademica',
        'id_materia',
    ];

    protected $casts = ['abierto' => 'boolean'];

    public function gestionAcademica()
    {
        return $this->belongsTo(GestionAcademica::class, 'id_gestionacademica', 'id_gestionacademica');
    }

    public function materia()
    {
        return $this->belongsTo(Materia::class, 'id_materia', 'id_materia');
    }

    public function docente()
    {
        return $this->belongsTo(Persona::class, 'id_persona', 'id_persona');
    }
}
