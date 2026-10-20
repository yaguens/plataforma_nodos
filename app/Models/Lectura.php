<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Lectura extends Model
{
    protected $table = 'lecturas';

    public $timestamps = false;

    protected $fillable = [
        'ciclo_id', 'nodo_id', 'sitio_id', 'parametro_id', 'sonda_id',
        'valor', 'codigo_estado', 'medido_en', 'marca',
    ];

    protected $casts = [
        'valor' => 'float',
        'codigo_estado' => 'integer',
        'medido_en' => 'datetime',
    ];

    public function ciclo(): BelongsTo
    {
        return $this->belongsTo(Ciclo::class);
    }

    public function nodo(): BelongsTo
    {
        return $this->belongsTo(Nodo::class);
    }

    public function sitio(): BelongsTo
    {
        return $this->belongsTo(Sitio::class);
    }

    public function parametro(): BelongsTo
    {
        return $this->belongsTo(Parametro::class);
    }

    public function sonda(): BelongsTo
    {
        return $this->belongsTo(Sonda::class);
    }
}
