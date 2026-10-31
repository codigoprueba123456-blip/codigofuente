<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Model;

class Persona extends Model
{
    protected $table = 'personas';
    protected $primaryKey = 'id_persona';
    protected $fillable = ['nombre', 'primerapellido', 'segundoapellido', 'ci', 'lugarexpedicion', 'fechanacimiento', 'lugarnacimiento', 'nacionalidad', 'sexo',
    'direccion','telefono','celular','email','tipo','foto'];

    public function estudiante(): HasOne
    {
        return $this->hasOne(Estudiante::class, 'id_persona', 'id_persona');
    }
}
