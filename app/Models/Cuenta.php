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
     * Informa al contrato de autenticación qué columna representa el username de esta cuenta.
     * Esto permite iniciar sesión con usuario en vez del campo convencional email.
     */
    public function username(): string
    {
        return 'usuario';
    }

    /**
     * Relaciona la cuenta de rol con la persona propietaria de las credenciales.
     */
    public function persona(): BelongsTo
    {
        return $this->belongsTo(Persona::class, 'id_persona', 'id_persona');
    }

    /**
     * Relaciona la cuenta con el rol que determina sus permisos de acceso.
     */
    public function rol(): BelongsTo
    {
        return $this->belongsTo(Rol::class, 'id_rol', 'id_rol');
    }
}