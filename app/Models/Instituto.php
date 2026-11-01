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

    public function rector(): BelongsTo
    {
        return $this->belongsTo(Empleado::class, 'id_rector', 'id_persona');
    }

    public function directorAcademico(): BelongsTo
    {
        return $this->belongsTo(Empleado::class, 'id_directoracademico', 'id_persona');
    }
}
