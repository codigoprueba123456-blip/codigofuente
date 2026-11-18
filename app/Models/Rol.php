<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Rol extends Model
{
    protected $table = 'rol';
    protected $primaryKey = 'id_rol';
    protected $fillable = ['nombre', 'activo'];

    protected $casts = ['activo' => 'boolean'];

    /**
     * Filtra roles habilitados para asignación.
     */
    public function scopeActivos($query)
    {
        return $query->where('activo', true);
    }

    /**
     * Obtiene las funcionalidades autorizadas para este rol.
     */
    public function recursos()
    {
        return $this->belongsToMany(Recurso::class, 'privilegio', 'id_rol', 'id_recurso');
    }

    /**
     * Obtiene las cuentas de usuario que tienen asignado este rol.
     */
    public function cuentas()
    {
        return $this->hasMany(Cuenta::class, 'id_rol', 'id_rol');
    }

    /**
     * Comprueba si el rol permite una URL después de quitar barras iniciales y finales.
     */
    public function tieneRecurso($url)
    {
        $urlNormalizada = trim((string) $url, '/');

        if ($urlNormalizada === '') {
            return true;
        }

        return $this->recursos()->where('urlpattern', $urlNormalizada)->exists();
    }
}