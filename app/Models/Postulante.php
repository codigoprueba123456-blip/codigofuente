<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Postulante extends Model
{
    use HasFactory;

    // Nombre exacto de la tabla en tu base de datos
    protected $table = 'postulante';

    // Llave primaria personalizada
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
        'path_nacimiento',  // <-- Añadido
        'path_diploma',     // <-- Añadido
        'id_carrera',
        'id_gestionacademica',
    ];

    // Relación con la tabla Carrera
    public function carrera()
    {
        return $this->belongsTo(Carrera::class, 'id_carrera', 'id_carrera');
    }

    // Relación con la tabla GestionAcademica
    public function gestionAcademica()
    {
        return $this->belongsTo(GestionAcademica::class, 'id_gestionacademica', 'id_gestionacademica');
    }

    // Alias para evitar errores cuando se consulta 'gestion'
    public function gestion()
    {
        return $this->belongsTo(GestionAcademica::class, 'id_gestionacademica', 'id_gestionacademica');
    }
}
