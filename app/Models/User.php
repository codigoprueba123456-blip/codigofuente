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
     * Devuelve la columna password que Laravel debe verificar durante el inicio de sesión.
     * El valor almacenado es un hash; nunca contiene la contraseña legible.
     */
    public function getAuthPassword()
    {
        return $this->password;
    }

    /**
     * Resuelve el atributo virtual name que esperan AdminLTE y otros componentes de autenticación.
     * Reutiliza persona si ya fue precargada para evitar otra consulta; si falta el vínculo devuelve username.
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
     * Relaciona el usuario autenticable con la persona que contiene sus datos personales.
     */
    public function persona()
    {
        return $this->belongsTo(Persona::class, 'id_persona', 'id_persona');
    }

    /**
     * Proporciona la cuenta de rol principal para pantallas heredadas que esperan una sola relación.
     */
    public function cuenta()
    {
        return $this->hasOne(Cuenta::class, 'id_persona', 'id_persona');
    }

    /**
     * Devuelve todos los vínculos usuario-rol cuando una persona puede tener más de una cuenta.
     */
    public function cuentas(): HasMany
    {
        return $this->hasMany(Cuenta::class, 'id_persona', 'id_persona');
    }

    /**
     * Relaciona las carreras cursadas por la persona mediante su historial de cursado.
     * El conjunto se usa, por ejemplo, para filtrar actividades dirigidas a carreras.
     */
    public function carreras()
    {
        return $this->belongsToMany(Carrera::class, 'cursa', 'id_persona', 'id_carrera', 'id_persona', 'id_carrera');
    }

    /**
     * Comprueba acceso a una ruta normalizada usando estado laboral, identidad y recursos de roles.
     * Los estudiantes solo pueden entrar al portal estudiantil si tienen ficha y rol exclusivamente estudiantil;
     * otros usuarios no acceden a ese portal y requieren un recurso coincidente en algún rol activo.
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
     * Reconoce al administrador mediante roles activos y contempla identidades antiguas sin cuentas/roles.
     * Si hay roles pero ninguno es administrador, no usa las reglas heredadas para elevar privilegios;
     * cualquier empleado inactivo queda excluido desde el inicio.
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
     * Carga roles activos, descarta vínculos sin nombre y normaliza alias históricos para comparaciones.
     * El resultado se reutiliza en las comprobaciones de acceso y administrador.
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
     * Permite acceso a personas que no son empleados y exige activo=true a quienes sí lo son.
     */
    public function isActive(): bool
    {
        return ! $this->empleado()->exists()
            || $this->empleado()->where('activo', true)->exists();
    }

    /**
     * Relaciona el usuario con su ficha de empleado, usada para evaluar si su acceso sigue activo.
     */
    public function empleado()
    {
        return $this->hasOne(Empleado::class, 'id_persona', 'id_persona');
    }

    /**
     * Mantiene el nombre de API que usan los filtros de menú y delega toda la decisión en tieneAccesoA().
     */
    public function canAccess($path)
    {
        return $this->tieneAccesoA($path);
    }

    /**
     * Convierte el rol a minúsculas y sustituye alias heredados como admin/alumno por nombres canónicos.
     * La normalización evita que diferencias de idioma o mayúsculas cambien las reglas de acceso.
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