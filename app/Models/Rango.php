<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Rango extends Model
{
    protected $table = 'rangos';

    protected $fillable = [
        'parametro_id', 'tipo_sitio', 'sitio_id',
        'optimo_min', 'optimo_max', 'aceptable_min', 'aceptable_max', 'fuente',
    ];

    protected $casts = [
        'optimo_min' => 'float',
        'optimo_max' => 'float',
        'aceptable_min' => 'float',
        'aceptable_max' => 'float',
    ];

    public function parametro(): BelongsTo
    {
        return $this->belongsTo(Parametro::class);
    }

    public function sitio(): BelongsTo
    {
        return $this->belongsTo(Sitio::class);
    }
}
