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
     * Campo identificador para inicio de sesión.
     */
    public function username(): string
    {
        return 'usuario';
    }

    /**
     * Relación pertenece a Persona.
     */
    public function persona(): BelongsTo
    {
        return $this->belongsTo(Persona::class, 'id_persona', 'id_persona');
    }

    /**
     * Relación pertenece a Rol.
     */
    public function rol(): BelongsTo
    {
        return $this->belongsTo(Rol::class, 'id_rol', 'id_rol');
    }
}