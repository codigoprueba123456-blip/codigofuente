<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;

class Privilegio extends Pivot
{
    protected $table = 'privilegio';
    public $incrementing = false; // Clave primaria compuesta
    protected $fillable = ['id_recurso', 'id_rol'];
}