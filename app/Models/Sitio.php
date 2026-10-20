<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Sitio extends Model
{
    protected $table = 'sitios';

    // Tipos de sitio (los rangos van por tipo)
    public const TIPOS = ['lago' => 'Lago', 'ptar' => 'PTAR', 'laboratorio' => 'Laboratorio', 'otro' => 'Otro'];

    protected $fillable = ['proyecto_id', 'nombre', 'tipo', 'pais', 'zona_horaria', 'latitud', 'longitud'];

    protected $casts = [
        'latitud' => 'float',
        'longitud' => 'float',
    ];

    public function proyecto(): BelongsTo
    {
        return $this->belongsTo(Proyecto::class);
    }

    public function nodos(): HasMany
    {
        return $this->hasMany(Nodo::class);
    }
}
