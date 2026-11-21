<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TarifaMatricula extends Model
{
    protected $table = 'tarifas_matricula';
    protected $primaryKey = 'id_tarifa_matricula';

    protected $fillable = [
        'id_gestionacademica',
        'id_carrera',
        'nivel',
        'monto',
    ];

    protected $casts = [
        'nivel' => 'integer',
        'monto' => 'decimal:2',
    ];

    /**
     * Relaciona la tarifa con el periodo que limita su vigencia académica.
     */
    public function gestionAcademica(): BelongsTo
    {
        return $this->belongsTo(GestionAcademica::class, 'id_gestionacademica', 'id_gestionacademica');
    }

    /**
     * Relaciona el monto configurado con la carrera que lo utilizará al calcular pagos.
     */
    public function carrera(): BelongsTo
    {
        return $this->belongsTo(Carrera::class, 'id_carrera', 'id_carrera');
    }
}
