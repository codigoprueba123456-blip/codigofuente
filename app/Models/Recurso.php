<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Recurso extends Model
{
    protected $table = 'recurso';
    protected $primaryKey = 'id_recurso';
    protected $fillable = ['nombre', 'urlpattern'];

    // Relación muchos a muchos con Rol (a través de la tabla privilegio)
    public function roles()
    {
        return $this->belongsToMany(Rol::class, 'privilegio', 'id_recurso', 'id_rol');
    }
}