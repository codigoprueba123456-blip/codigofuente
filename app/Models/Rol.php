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
     * Restringe una consulta a roles activos para asignarlos a usuarios o cuentas nuevas.
     */
    public function scopeActivos($query)
    {
        return $query->where('activo', true);
    }

    /**
     * Relaciona el rol con los recursos que autorizan rutas de la aplicación.
     * La asignación se persiste en la tabla pivote privilegio.
     */
    public function recursos()
    {
        return $this->belongsToMany(Recurso::class, 'privilegio', 'id_rol', 'id_recurso');
    }

    /**
     * Devuelve las cuentas que usan este rol y heredan sus permisos.
     */
    public function cuentas()
    {
        return $this->hasMany(Cuenta::class, 'id_rol', 'id_rol');
    }

    /**
     * Normaliza la ruta quitando barras externas y comprueba su coincidencia exacta en los recursos.
     * Una ruta vacía se considera permitida porque no representa un recurso restringido.
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