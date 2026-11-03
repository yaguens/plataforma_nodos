<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Proyecto extends Model
{
    protected $table = 'proyectos';

    // Estados posibles (los usan la validación y el desplegable del formulario)
    public const ESTADOS = ['Activo', 'Pausado', 'Finalizado'];

    protected $fillable = ['nombre', 'descripcion', 'estado'];

    public function sitios(): HasMany
    {
        return $this->hasMany(Sitio::class);
    }
}
