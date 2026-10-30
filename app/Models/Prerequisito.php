<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Pivot;

class Prerequisito extends Pivot
{
    protected $table = 'prerequisito';

    public $incrementing = false;

    protected $primaryKey = null;

    protected $fillable = [
        'id_materia',
        'id_prerequisito',
    ];

    public function materia(): BelongsTo
    {
        return $this->belongsTo(Materia::class, 'id_materia', 'id_materia');
    }

    public function materiaPrerequisito(): BelongsTo
    {
        return $this->belongsTo(Materia::class, 'id_prerequisito', 'id_materia');
    }
}