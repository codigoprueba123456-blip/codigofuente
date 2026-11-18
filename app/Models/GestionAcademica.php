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
        'activo',
    ];

    protected $casts = [
        'vigente' => 'boolean',
        'activo' => 'boolean',
    ];

    /**
     * Construye la etiqueta corta según el régimen: anual, semestral o periodo no estándar.
     */
    public function getEtiquetaAttribute(): string
    {
        $periodo = (int) $this->periodo;

        if ((int) $this->regimen === Carrera::REGIMEN_ANUAL) {
            return 'I' . $this->gestion . 'A';
        }

        if ((int) $this->regimen === Carrera::REGIMEN_SEMESTRAL && in_array($periodo, [1, 2], true)) {
            return ($periodo === 1 ? 'I' : 'II') . $this->gestion . 'S';
        }

        return $this->gestion . ' - Periodo ' . $this->periodo;
    }

    /**
     * Filtra gestiones que están habilitadas y marcadas como vigentes.
     */
    public function scopeHabilitadas($query)
    {
        return $query->where('vigente', true)->where('activo', true);
    }

    /**
     * Obtiene las actividades asociadas a esta gestión académica.
     */
    public function actividades()
    {
        return $this->hasMany(Actividad::class, 'id_gestionacademica');
    }

    /**
     * Obtiene los grupos programados para esta gestión académica.
     */
    public function grupos()
    {
        return $this->hasMany(Grupo::class, 'id_gestionacademica');
    }
}