<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $table = 'users';
    protected $primaryKey = 'id_persona';
    public $incrementing = false; // Indica que no es autoincremental
    protected $keyType = 'unsignedBigInteger'; // O el tipo de dato de id_persona (int/string)

    protected $fillable = [
        'id_persona',
        'usuario',
        'password',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Mapea la columna de contraseña real para Laravel Auth.
     */
    public function getAuthPassword()
    {
        return $this->password;
    }

    public function getNameAttribute()
    {
        if ($this->relationLoaded('persona') && $this->persona) {
            return trim(($this->persona->nombre ?? '') . ' ' . ($this->persona->primerapellido ?? ''));
        }

        $persona = $this->persona()->first();

        if ($persona) {
            return trim(($persona->nombre ?? '') . ' ' . ($persona->primerapellido ?? ''));
        }

        return $this->usuario ?? 'Usuario';
    }

    /**
     * Relación uno a uno / pertenece a Persona.
     */
    public function persona()
    {
        return $this->belongsTo(Persona::class, 'id_persona', 'id_persona');
    }

    /**
     * Relación uno a muchos con Cuenta.
     */
    public function cuenta()
    {
        return $this->hasOne(Cuenta::class, 'id_persona', 'id_persona');
    }

    public function cuentas(): HasMany
    {
        return $this->hasMany(Cuenta::class, 'id_persona', 'id_persona');
    }

    public function carreras()
    {
        return $this->belongsToMany(Carrera::class, 'cursa', 'id_persona', 'id_carrera', 'id_persona', 'id_carrera');
    }

    public function tieneAccesoA($url)
    {
        $urlNormalizada = trim((string) $url, '/');

        if ($urlNormalizada === '') {
            return true;
        }

        $roleNames = $this->cuentas()
            ->with('rol:id_rol,nombre')
            ->get()
            ->pluck('rol.nombre')
            ->filter()
            ->map(fn (string $name): string => $this->normalizeRole($name));
        $personaType = $this->normalizeRole((string) $this->persona?->tipo);
        $isStudentIdentity = $roleNames->contains('estudiante') || $personaType === 'estudiante';
        $isStudentPortalPath = str_starts_with($urlNormalizada, 'estudiantes/');

        if ($isStudentIdentity) {
            $hasStudentRecord = $this->persona?->estudiante()->exists() ?? false;
            $hasOnlyStudentRole = $roleNames->isEmpty()
                ? $personaType === 'estudiante'
                : $roleNames->every(static fn (string $role): bool => $role === 'estudiante');

            return $isStudentPortalPath && $hasStudentRecord && $hasOnlyStudentRole;
        }

        if ($isStudentPortalPath) {
            return false;
        }

        if ($roleNames->isEmpty()) {
            return in_array($personaType, ['admin', 'administrador'], true)
                || (int) $this->id_persona === 1
                || $this->usuario === '20260001';
        }

        if ($roleNames->contains('administrador')) {
            return true;
        }

        return $this->cuentas()
            ->whereHas('rol.recursos', fn ($query) => $query->where('urlpattern', $urlNormalizada))
            ->exists();
    }

    public function canAccess($path)
    {
        return $this->tieneAccesoA($path);
    }

    private function normalizeRole(string $role): string
    {
        return match (mb_strtolower(trim($role))) {
            'admin' => 'administrador',
            'alumno', 'student' => 'estudiante',
            default => mb_strtolower(trim($role)),
        };
    }
}