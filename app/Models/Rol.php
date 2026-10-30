<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Rol extends Model
{
    protected $table = 'rol';
    protected $primaryKey = 'id_rol';
    protected $fillable = ['nombre'];

    // Relación muchos a muchos con Recurso (a través de la tabla privilegio)
    public function recursos()
    {
        return $this->belongsToMany(Recurso::class, 'privilegio', 'id_rol', 'id_recurso');
    }

    // Relación uno a muchos con Cuenta
    public function cuentas()
    {
        return $this->hasMany(Cuenta::class, 'id_rol', 'id_rol');
    }

    public function tieneRecurso($url)
    {
        $urlNormalizada = trim((string) $url, '/');

        if ($urlNormalizada === '') {
            return true;
        }

        return $this->recursos()->where('urlpattern', $urlNormalizada)->exists();
    }
}