<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Incidencia extends Model
{
    protected $table = 'incidencias';

    public $timestamps = false;

    protected $fillable = [
        'tipo', 'motivo', 'nodo_id', 'codigo_nodo', 'seq', 'origen', 'recibido_en', 'cuerpo_original', 'revisada',
    ];

    protected $casts = [
        'seq' => 'integer',
        'recibido_en' => 'datetime',
        'revisada' => 'boolean',
    ];

    public function nodo(): BelongsTo
    {
        return $this->belongsTo(Nodo::class);
    }
}
