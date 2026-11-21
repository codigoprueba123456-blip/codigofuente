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
     * Genera el identificador legible usado en selectores y encabezados.
     * Los periodos anuales usan formato I{año}A; los dos semestres usan I/II{año}S;
     * otros valores de periodo se muestran con una etiqueta descriptiva genérica.
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
     * Limita la consulta a gestiones simultáneamente activas y vigentes.
     * Una gestión que cumpla solo uno de esos estados no debe aparecer en operaciones disponibles.
     */
    public function scopeHabilitadas($query)
    {
        return $query->where('vigente', true)->where('activo', true);
    }

    /**
     * Relaciona los eventos del calendario cuyo campo id_gestionacademica apunta a este periodo.
     */
    public function actividades()
    {
        return $this->hasMany(Actividad::class, 'id_gestionacademica');
    }

    /**
     * Relaciona los grupos académicos programados para este periodo.
     */
    public function grupos()
    {
        return $this->hasMany(Grupo::class, 'id_gestionacademica');
    }

    /**
     * Relaciona las tarifas configuradas dentro de esta gestión.
     * Cada registro puede corresponder a una carrera completa (nivel cero) o a un nivel específico.
     */
    public function tarifasMatricula()
    {
        return $this->hasMany(TarifaMatricula::class, 'id_gestionacademica', 'id_gestionacademica');
    }
}