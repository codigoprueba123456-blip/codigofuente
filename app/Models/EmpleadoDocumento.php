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

    /**
     * Convierte el índice 1-4 del arreglo de archivos del formulario en el tipo persistido.
     * Para un índice desconocido genera una etiqueta genérica en lugar de fallar.
     */
    public static function tipoParaSlot(int $slot): string
    {
        return self::TIPOS_POR_SLOT[$slot] ?? 'Documento ' . $slot;
    }

    /**
     * Proporciona el texto visible del campo reutilizando el mismo catálogo de tipos almacenados.
     */
    public static function nombreParaSlot(int $slot): string
    {
        return self::tipoParaSlot($slot);
    }

    /**
     * Relaciona el documento con el empleado usando id_persona como clave en ambos modelos.
     */
    public function empleado()
    {
        return $this->belongsTo(Empleado::class, 'id_persona', 'id_persona');
    }
}
