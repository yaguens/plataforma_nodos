<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Parametro extends Model
{
    protected $table = 'parametros';

    public const ESTADOS = ['Pendiente', 'Activo', 'Oculto'];

    protected $fillable = [
        'codigo', 'nombre', 'unidad', 'decimales', 'escala_min', 'escala_max', 'mostrar_medidor', 'estado',
    ];

    protected $casts = [
        'decimales' => 'integer',
        'escala_min' => 'float',
        'escala_max' => 'float',
        'mostrar_medidor' => 'boolean',
    ];

    public function lecturas(): HasMany
    {
        return $this->hasMany(Lectura::class);
    }

    public function rangos(): HasMany
    {
        return $this->hasMany(Rango::class);
    }

    // Sensor de una clave: lo que va antes del primer "_" (OD_OD_medida → OD)
    public static function fuenteDe(string $codigo): string
    {
        $pos = strpos($codigo, '_');
        return $pos === false ? $codigo : substr($codigo, 0, $pos);
    }
}
