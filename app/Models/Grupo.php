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
     * Restringe una consulta a grupos activos, independientemente de que acepten nuevas inscripciones.
     * El estado abierto se consulta por separado cuando se valida disponibilidad.
     */
    public function scopeActivos($query)
    {
        return $query->where('activo', true);
    }

    /**
     * Relaciona el grupo con el periodo académico durante el que se ofrece.
     */
    public function gestionAcademica()
    {
        return $this->belongsTo(GestionAcademica::class, 'id_gestionacademica', 'id_gestionacademica');
    }

    /**
     * Relaciona el grupo con la materia que se dicta en su paralelo y turno.
     */
    public function materia()
    {
        return $this->belongsTo(Materia::class, 'id_materia', 'id_materia');
    }

    /**
     * Relaciona id_persona con la persona que figura como docente del grupo.
     */
    public function docente()
    {
        return $this->belongsTo(Persona::class, 'id_persona', 'id_persona');
    }

    /**
     * Relaciona el grupo con sus inscripciones de estudiantes mediante inscrito_materia.
     * Incluye timestamps de la tabla pivote para conservar cuándo se vinculó cada inscripción.
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
