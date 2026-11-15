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

    public function scopeActivas($query)
    {
        return $query->where('activo', true);
    }

    public function carrera()
    {
        return $this->belongsTo(Carrera::class, 'id_carrera', 'id_carrera');
    }

    public function grupos()
    {
        return $this->hasMany(Grupo::class, 'id_materia', 'id_materia');
    }

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
