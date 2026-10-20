<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Proyecto extends Model
{
    protected $table = 'proyectos';

    protected $fillable = ['nombre', 'descripcion', 'estado'];

    public function sitios(): HasMany
    {
        return $this->hasMany(Sitio::class);
    }
}
