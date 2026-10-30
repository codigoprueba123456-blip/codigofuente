<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GestionAcademica extends Model
{
    use HasFactory;

    protected $table = 'gestionacademica';
    protected $primaryKey = 'id_gestionacademica';

    protected $fillable = [
        'gestion',
        'periodo',
        'regimen',
        'inicio',
        'fin',
        'vigente',
        'modalidadevaluacion',
    ];

    protected $casts = [
        'vigente' => 'boolean',
    ];

    public function scopeHabilitadas($query)
    {
        return $query->where('vigente', true);
    }

    public function actividades()
    {
        return $this->hasMany(Actividad::class, 'id_gestionacademica');
    }

    public function grupos()
    {
        return $this->hasMany(Grupo::class, 'id_gestionacademica');
    }
}