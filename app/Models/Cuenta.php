<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Cuenta extends Authenticatable
{
    use Notifiable;

    protected $table = 'cuenta';
    protected $primaryKey = 'id_persona';
    public $incrementing = false;
    protected $keyType = 'unsignedBigInteger';

    protected $fillable = [
        'id_persona',
        'id_rol',
        'usuario',
        'password',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Indica a Laravel Auth que el nombre de usuario de esta cuenta está en la columna usuario.
     */
    public function username(): string
    {
        return 'usuario';
    }

    /**
     * Obtiene la persona propietaria de la cuenta y sus datos de identidad.
     */
    public function persona(): BelongsTo
    {
        return $this->belongsTo(Persona::class, 'id_persona', 'id_persona');
    }

    /**
     * Obtiene el rol cuyos permisos están asociados a esta cuenta.
     */
    public function rol(): BelongsTo
    {
        return $this->belongsTo(Rol::class, 'id_rol', 'id_rol');
    }
}