<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Materia extends Model
{
    protected $table = 'materia';
    protected $primaryKey = 'id_materia';
    protected $fillable = [
        'activo',
        'codigo',
        'nombre',
        'creditajemateria',
        'curricular',
        'horas',
        'nivel',
        'numero',
        'id_carrera',
    ];

    protected $casts = [
        'activo' => 'boolean',
        'curricular' => 'boolean',
    ];

    /**
     * Restringe la consulta a materias activas para altas, programación y selección curricular.
     */
    public function scopeActivas($query)
    {
        return $query->where('activo', true);
    }

    /**
     * Relaciona la materia con la carrera cuyo plan curricular la contiene.
     */
    public function carrera()
    {
        return $this->belongsTo(Carrera::class, 'id_carrera', 'id_carrera');
    }

    /**
     * Devuelve los grupos en los que esta materia se programa a través de las gestiones académicas.
     */
    public function grupos()
    {
        return $this->hasMany(Grupo::class, 'id_materia', 'id_materia');
    }

    /**
     * Devuelve materias que deben aprobarse antes de cursar esta materia.
     * La tabla pivote registra esta materia como id_materia y cada requisito como id_prerequisito.
     */
    public function prerequisitos()
    {
        return $this->belongsToMany(
            self::class,
            'prerequisito',
            'id_materia',
            'id_prerequisito',
            'id_materia',
            'id_materia'
        )->withTimestamps();
    }

    /**
     * Devuelve materias posteriores cuyo requisito es esta materia.
     * Usa las mismas filas pivote en dirección inversa para consultar dependencias curriculares.
     */
    public function materiasDependientes()
    {
        return $this->belongsToMany(
            self::class,
            'prerequisito',
            'id_prerequisito',
            'id_materia',
            'id_materia',
            'id_materia'
        )->withTimestamps();
    }
}
