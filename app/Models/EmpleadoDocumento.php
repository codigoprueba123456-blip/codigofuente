<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EmpleadoDocumento extends Model
{
    protected $table = 'empleado_documentos';

    private const TIPOS_POR_SLOT = [
        1 => 'Carnet de identidad (CI)',
        2 => 'Título en provisión nacional',
        3 => 'Diplomado en educación superior',
        4 => 'Certificado de quechua',
    ];

    protected $fillable = ['id_persona', 'tipo', 'ruta'];

    public static function tipoParaSlot(int $slot): string
    {
        return self::TIPOS_POR_SLOT[$slot] ?? 'Documento ' . $slot;
    }

    public static function nombreParaSlot(int $slot): string
    {
        return self::tipoParaSlot($slot);
    }

    public function empleado()
    {
        return $this->belongsTo(Empleado::class, 'id_persona', 'id_persona');
    }
}
