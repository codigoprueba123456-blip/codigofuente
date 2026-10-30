<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EmpleadoDocumento extends Model
{
    protected $table = 'empleado_documentos';

    protected $fillable = ['id_persona', 'slot', 'ruta'];

    public function empleado()
    {
        return $this->belongsTo(Empleado::class, 'id_persona', 'id_persona');
    }
}
