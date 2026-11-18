<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;

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
     * Devuelve el hash almacenado en password para la autenticación de Laravel.
     */
    public function getAuthPassword()
    {
        return $this->password;
    }

    /**
     * Expone el nombre completo de la persona; usa la relación cargada o consulta la base de datos
     * y, si no hay persona asociada, recurre al username.
     */
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
     * Obtiene la persona propietaria de esta cuenta de acceso.
     */
    public function persona()
    {
        return $this->belongsTo(Persona::class, 'id_persona', 'id_persona');
    }

    /**
     * Obtiene la cuenta de rol principal usada por las vistas que esperan una sola cuenta.
     */
    public function cuenta()
    {
        return $this->hasOne(Cuenta::class, 'id_persona', 'id_persona');
    }

    /**
     * Obtiene todas las cuentas de rol asociadas al usuario.
     */
    public function cuentas(): HasMany
    {
        return $this->hasMany(Cuenta::class, 'id_persona', 'id_persona');
    }

    /**
     * Obtiene las carreras en las que el estudiante está registrado.
     */
    public function carreras()
    {
        return $this->belongsToMany(Carrera::class, 'cursa', 'id_persona', 'id_carrera', 'id_persona', 'id_carrera');
    }

    /**
     * Decide si el usuario puede acceder a una ruta: aplica reglas especiales al portal estudiantil
     * y, para otros usuarios, comprueba los recursos de sus roles activos.
     */
    public function tieneAccesoA($url)
    {
        $urlNormalizada = trim((string) $url, '/');

        if (! $this->isActive()) {
            return false;
        }

        if ($urlNormalizada === '' || $this->isAdministrator()) {
            return true;
        }

        $roleNames = $this->roleNames();
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

        return $this->cuentas()
            ->whereHas('rol', fn ($query) => $query->where('activo', true)
                ->whereHas('recursos', fn ($resource) => $resource->where('urlpattern', $urlNormalizada)))
            ->exists();
    }

    /**
     * Reconoce administradores por su rol activo y mantiene compatibilidad con cuentas heredadas
     * que todavía no tienen roles asociados.
     */
    public function isAdministrator(): bool
    {
        if (! $this->isActive()) {
            return false;
        }

        $roleNames = $this->roleNames();
        if ($roleNames->contains('administrador')) {
            return true;
        }

        if ($roleNames->isNotEmpty()) {
            return false;
        }

        if ($this->cuentas()->exists()) {
            return false;
        }

        return in_array($this->normalizeRole((string) $this->persona?->tipo), ['admin', 'administrador'], true)
            || (int) $this->id_persona === 1
            || $this->usuario === '20260001';
    }

    /**
     * Obtiene nombres normalizados de los roles activos del usuario.
     */
    private function roleNames(): Collection
    {
        return $this->cuentas()
            ->whereHas('rol', fn ($query) => $query->where('activo', true))
            ->with('rol:id_rol,nombre')
            ->get()
            ->pluck('rol.nombre')
            ->filter()
            ->map(fn (string $name): string => $this->normalizeRole($name));
    }

    /**
     * Considera activo a quien no es empleado o tiene un registro laboral habilitado.
     */
    public function isActive(): bool
    {
        return ! $this->empleado()->exists()
            || $this->empleado()->where('activo', true)->exists();
    }

    /**
     * Obtiene el registro laboral de la persona, cuando existe.
     */
    public function empleado()
    {
        return $this->hasOne(Empleado::class, 'id_persona', 'id_persona');
    }

    /**
     * Alias usado por los filtros de menú para consultar permisos de acceso.
     */
    public function canAccess($path)
    {
        return $this->tieneAccesoA($path);
    }

    /**
     * Normaliza nombres de roles y alias históricos para comparar permisos de manera uniforme.
     */
    private function normalizeRole(string $role): string
    {
        return match (mb_strtolower(trim($role))) {
            'admin' => 'administrador',
            'alumno', 'student' => 'estudiante',
            default => mb_strtolower(trim($role)),
        };
    }
}