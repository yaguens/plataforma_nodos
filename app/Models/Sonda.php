<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Sonda extends Model
{
    protected $table = 'sondas';

    protected $fillable = [
        'nodo_id', 'fuente', 'modelo', 'numero_serie',
        'instalada_desde', 'instalada_hasta', 'ultima_calibracion', 'notas',
    ];

    protected $casts = [
        'instalada_desde' => 'datetime',
        'instalada_hasta' => 'datetime',
        'ultima_calibracion' => 'date',
    ];

    public function nodo(): BelongsTo
    {
        return $this->belongsTo(Nodo::class);
    }
}
