<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Ciclo extends Model
{
    protected $table = 'ciclos';

    public $timestamps = false;   // ya tiene recibido_en

    protected $fillable = [
        'nodo_id', 'seq', 'ts', 'rtc', 'formato', 'medido_en', 'recibido_en',
        'origen', 'via_nodo_id', 'marcas', 'cuerpo_original',
    ];

    protected $casts = [
        'seq' => 'integer',
        'ts' => 'integer',
        'rtc' => 'integer',
        'formato' => 'integer',
        'medido_en' => 'datetime',
        'recibido_en' => 'datetime',
    ];

    public function nodo(): BelongsTo
    {
        return $this->belongsTo(Nodo::class);
    }

    public function via(): BelongsTo
    {
        return $this->belongsTo(Nodo::class, 'via_nodo_id');
    }

    public function lecturas(): HasMany
    {
        return $this->hasMany(Lectura::class);
    }
}
