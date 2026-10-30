<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class Actividad extends Model
{
    protected $table = 'actividad';
    protected $primaryKey = 'id_actividad';
    protected $fillable = [
        'nombre',
        'descripcion',
        'inicio',
        'fin',
        'funcionalidad',
        'id_gestionacademica',
    ];

    public function gestionAcademica()
    {
        return $this->belongsTo(GestionAcademica::class, 'id_gestionacademica', 'id_gestionacademica');
    }

    public function funcionalidadRecurso()
    {
        return $this->belongsTo(Recurso::class, 'funcionalidad', 'id_recurso');
    }

    public function carreras()
    {
        return $this->belongsToMany(Carrera::class, 'actividad_carrera', 'id_actividad', 'id_carrera', 'id_actividad', 'id_carrera');
    }

    public function usuarios()
    {
        return $this->belongsToMany(User::class, 'actividad_usuario', 'id_actividad', 'id_persona', 'id_actividad', 'id_persona');
    }

    public function scopeVisibleTo(Builder $query, User $user): Builder
    {
        $careerIds = $user->carreras()->pluck('carreras.id_carrera');

        return $query->where(function (Builder $visibility) use ($user, $careerIds) {
            $visibility->whereDoesntHave('carreras')
                ->whereDoesntHave('usuarios')
                ->orWhereHas('usuarios', fn (Builder $users) => $users->where('users.id_persona', $user->id_persona));

            if ($careerIds->isNotEmpty()) {
                $visibility->orWhereHas('carreras', fn (Builder $careers) => $careers->whereIn('carreras.id_carrera', $careerIds));
            }
        });
    }
}
