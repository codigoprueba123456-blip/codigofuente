<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * Representa una postulación y los datos entregados para una carrera y gestión.
 * Sus documentos y datos académicos pueden convertirse en parte de una ficha de estudiante.
 */
class Postulante extends Model
{
    use HasFactory;

    // Esta entidad usa una tabla cuyo nombre no sigue la convención singular de Eloquent.
    protected $table = 'postulante';

    // Identificador propio de cada postulación.
    protected $primaryKey = 'id_postulante';

    protected $casts = [
        'fechanacimiento' => 'date',
        'fecha' => 'datetime',
        'diplomabachiller' => 'boolean',
    ];

    protected $fillable = [
        'codigo',
        'ci',
        'primerapellido',
        'segundoapellido',
        'nombre',
        'celular',
        'telefono',
        'email',
        'direccion',
        'fechanacimiento',
        'lugarnacimiento',
        'lugarexpedicion',
        'nacionalidad',
        'sexo',
        'foto',
        'fecha',
        'nombrecolegio',
        'caractercolegio',
        'egresocolegio',
        'diplomabachiller',
        'nombrecontacto',
        'parentescocontacto',
        'celularcontacto',
        'deposito',
        'path_ci_frontal',
        'path_ci_reverso',
        'path_nacimiento',
        'path_diploma',
        'id_carrera',
        'id_gestionacademica',
        'turno',
        'paralelo',
    ];

    /**
     * Obtiene la carrera a la que corresponde la postulación.
     */
    public function carrera()
    {
        return $this->belongsTo(Carrera::class, 'id_carrera', 'id_carrera');
    }

    /**
     * Obtiene la gestión académica para la que se postuló.
     */
    public function gestionAcademica()
    {
        return $this->belongsTo(GestionAcademica::class, 'id_gestionacademica', 'id_gestionacademica');
    }

    /**
     * Mantiene el nombre de relación gestion usado por consultas existentes.
     */
    public function gestion()
    {
        return $this->belongsTo(GestionAcademica::class, 'id_gestionacademica', 'id_gestionacademica');
    }

    /**
     * Obtiene la inscripción creada a partir de esta postulación, si ya fue procesada.
     */
    public function inscripcionOrigen(): HasOne
    {
        return $this->hasOne(Inscrito::class, 'id_postulante_origen', 'id_postulante');
    }
}
