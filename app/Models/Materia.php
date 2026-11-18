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
     * Filtra materias habilitadas para selección académica.
     */
    public function scopeActivas($query)
    {
        return $query->where('activo', true);
    }

    /**
     * Obtiene la carrera propietaria de esta materia.
     */
    public function carrera()
    {
        return $this->belongsTo(Carrera::class, 'id_carrera', 'id_carrera');
    }

    /**
     * Obtiene los grupos programados para esta materia.
     */
    public function grupos()
    {
        return $this->hasMany(Grupo::class, 'id_materia', 'id_materia');
    }

    /**
     * Obtiene las materias que deben cursarse antes que esta materia.
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
     * Obtiene las materias que tienen esta materia como prerrequisito.
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
