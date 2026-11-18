<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class Actividad extends Model
{
    protected $table = 'actividad';
    protected $primaryKey = 'id_actividad';
    protected $fillable = [
        'activo',
        'nombre',
        'descripcion',
        'inicio',
        'fin',
        'funcionalidad',
        'id_gestionacademica',
    ];

    protected $casts = [
        'activo' => 'boolean',
        'inicio' => 'datetime',
        'fin' => 'datetime',
    ];

    /**
     * Obtiene la gestión académica a la que pertenece la actividad.
     */
    public function gestionAcademica()
    {
        return $this->belongsTo(GestionAcademica::class, 'id_gestionacademica', 'id_gestionacademica');
    }

    /**
     * Obtiene el recurso asociado por el campo de funcionalidad heredado.
     */
    public function funcionalidadRecurso()
    {
        return $this->belongsTo(Recurso::class, 'funcionalidad', 'id_recurso');
    }

    /**
     * Obtiene todos los recursos que la actividad habilita actualmente.
     */
    public function funcionalidades()
    {
        return $this->belongsToMany(
            Recurso::class,
            'actividad_funcionalidad',
            'id_actividad',
            'id_recurso',
            'id_actividad',
            'id_recurso'
        );
    }

    /**
     * Obtiene las carreras que forman parte de la audiencia de la actividad.
     */
    public function carreras()
    {
        return $this->belongsToMany(Carrera::class, 'actividad_carrera', 'id_actividad', 'id_carrera', 'id_actividad', 'id_carrera');
    }

    /**
     * Obtiene los usuarios seleccionados explícitamente como audiencia.
     */
    public function usuarios()
    {
        return $this->belongsToMany(User::class, 'actividad_usuario', 'id_actividad', 'id_persona', 'id_actividad', 'id_persona');
    }

    /**
     * Limita la consulta a actividades activas generales, dirigidas al usuario o a sus carreras.
     */
    public function scopeVisibleTo(Builder $query, User $user): Builder
    {
        $careerIds = $user->carreras()->pluck('carreras.id_carrera');

        return $query->where('activo', true)->where(function (Builder $visibility) use ($user, $careerIds) {
            $visibility->whereDoesntHave('carreras')
                ->whereDoesntHave('usuarios')
                ->orWhereHas('usuarios', fn (Builder $users) => $users->where('users.id_persona', $user->id_persona));

            if ($careerIds->isNotEmpty()) {
                $visibility->orWhereHas('carreras', fn (Builder $careers) => $careers->whereIn('carreras.id_carrera', $careerIds));
            }
        });
    }
}
