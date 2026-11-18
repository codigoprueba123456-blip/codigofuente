<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Grupo extends Model
{
    protected $table = 'grupo';
    protected $primaryKey = 'id_grupo';
    protected $fillable = [
        'activo',
        'codigo',
        'capacidad',
        'turno',
        'abierto',
        'id_persona',
        'id_gestionacademica',
        'id_materia',
    ];

    protected $casts = [
        'activo' => 'boolean',
        'abierto' => 'boolean',
    ];

    /**
     * Filtra los grupos que están habilitados.
     */
    public function scopeActivos($query)
    {
        return $query->where('activo', true);
    }

    /**
     * Obtiene la gestión académica en la que se programó el grupo.
     */
    public function gestionAcademica()
    {
        return $this->belongsTo(GestionAcademica::class, 'id_gestionacademica', 'id_gestionacademica');
    }

    /**
     * Obtiene la materia a la que corresponde este grupo.
     */
    public function materia()
    {
        return $this->belongsTo(Materia::class, 'id_materia', 'id_materia');
    }

    /**
     * Obtiene a la persona asignada como docente del grupo.
     */
    public function docente()
    {
        return $this->belongsTo(Persona::class, 'id_persona', 'id_persona');
    }

    /**
     * Obtiene las inscripciones de estudiantes asociadas al grupo, con fechas de vínculo.
     */
    public function inscritos()
    {
        return $this->belongsToMany(
            Inscrito::class,
            'inscrito_materia',
            'id_grupo',
            'id_inscrito',
            'id_grupo',
            'id_inscrito'
        )->withTimestamps();
    }
}
