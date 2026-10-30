<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Instituto extends Model
{
    protected $table = 'instituto';
    protected $primaryKey = 'id_instituto';
    protected $fillable = [
        'nombreinstituto', 'nombreregulador', 'ubicacion', 'caracter', 'nombreimpuestos', 'nit',
        'actividadeconomica', 'preciocredito', 'resolucionministerial',
        'abreviatura', 'token', 'logo', 'telefono', 'id_directoracademico', 'id_rector'
    ];
}
