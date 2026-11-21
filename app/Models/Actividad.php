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
     * Relaciona la actividad con el periodo que determina cuándo se organiza y administra.
     * Usa explícitamente id_gestionacademica porque el nombre de la clave no sigue la convención estándar.
     */
    public function gestionAcademica()
    {
        return $this->belongsTo(GestionAcademica::class, 'id_gestionacademica', 'id_gestionacademica');
    }

    /**
     * Resuelve la funcionalidad única antigua almacenada directamente en actividad.funcionalidad.
     * Se conserva para leer registros previos; las nuevas actividades usan funcionalidades().
     */
    public function funcionalidadRecurso()
    {
        return $this->belongsTo(Recurso::class, 'funcionalidad', 'id_recurso');
    }

    /**
     * Relaciona todas las secciones habilitadas por esta actividad mediante la tabla pivote actual.
     * A diferencia de funcionalidadRecurso(), admite varias funcionalidades por evento.
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
     * Relaciona las carreras seleccionadas como audiencia del evento.
     * La tabla pivote permite que una actividad sea visible para una o varias carreras.
     */
    public function carreras()
    {
        return $this->belongsToMany(Carrera::class, 'actividad_carrera', 'id_actividad', 'id_carrera', 'id_actividad', 'id_carrera');
    }

    /**
     * Relaciona las cuentas seleccionadas directamente como audiencia del evento.
     * Se utiliza junto con carreras() para distinguir audiencia individual de audiencia por carrera.
     */
    public function usuarios()
    {
        return $this->belongsToMany(User::class, 'actividad_usuario', 'id_actividad', 'id_persona', 'id_actividad', 'id_persona');
    }

    /**
     * Aplica el filtro de visibilidad para usuarios no administradores.
     * Incluye solo eventos activos de audiencia general, eventos dirigidos directamente al usuario
     * y eventos asignados a alguna carrera que el usuario cursa.
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
