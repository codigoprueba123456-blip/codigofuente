<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Instituto extends Model
{
    protected $table = 'instituto';
    protected $primaryKey = 'id_instituto';
    protected $fillable = [
        'nombreinstituto', 'nombreregulador', 'ubicacion', 'caracter', 'nombreimpuestos', 'nit',
        'actividadeconomica', 'preciocredito', 'resolucionministerial',
        'abreviatura', 'logo', 'telefono', 'id_directoracademico', 'id_rector'
    ];

    /**
     * Relaciona id_rector con el empleado que ocupa la autoridad de Rector.
     */
    public function rector(): BelongsTo
    {
        return $this->belongsTo(Empleado::class, 'id_rector', 'id_persona');
    }

    /**
     * Relaciona id_directoracademico con el empleado responsable del área académica.
     */
    public function directorAcademico(): BelongsTo
    {
        return $this->belongsTo(Empleado::class, 'id_directoracademico', 'id_persona');
    }
}
