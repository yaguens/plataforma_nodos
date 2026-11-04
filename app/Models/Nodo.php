<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Nodo extends Model
{
    protected $table = 'nodos';

    public const ROLES = ['maestro' => 'Maestro', 'esclavo' => 'Esclavo', 'independiente' => 'Independiente'];

    protected $fillable = [
        'sitio_id', 'codigo', 'nombre', 'rol', 'maestro_id', 'desfase_s', 'clave_api',
        'activo', 'ultimo_seq', 'ultima_conexion', 'primer_contacto', 'descripcion',
    ];

    protected $hidden = ['clave_api'];

    protected $casts = [
        'activo' => 'boolean',
        'desfase_s' => 'integer',
        'ultimo_seq' => 'integer',
        'ultima_conexion' => 'datetime',
        'primer_contacto' => 'datetime',
    ];

    public function sitio(): BelongsTo
    {
        return $this->belongsTo(Sitio::class);
    }

    public function maestro(): BelongsTo
    {
        return $this->belongsTo(Nodo::class, 'maestro_id');
    }

    public function sondas(): HasMany
    {
        return $this->hasMany(Sonda::class);
    }

    public function ciclos(): HasMany
    {
        return $this->hasMany(Ciclo::class);
    }

    public function lecturas(): HasMany
    {
        return $this->hasMany(Lectura::class);
    }

    // Estado de conexión: [texto, color de la etiqueta]
    public function estado(): array
    {
        if (! $this->activo) {
            return ['Desactivado', 'gris'];
        }
        if (! $this->ultima_conexion) {
            return ['Nunca conectado', 'gris'];
        }
        $minutos = $this->ultima_conexion->diffInMinutes(now(), true);
        if ($minutos <= config('plataforma.minutos_en_linea')) {
            return ['En línea', 'verde'];
        }
        if ($minutos <= config('plataforma.horas_sin_conexion') * 60) {
            return ['En silencio', 'ambar'];
        }
        return ['Sin conexión', 'rojo'];
    }

    // Zona horaria con la que se muestran sus horas (la de su sitio o la de por defecto)
    public function zonaHoraria(): string
    {
        return $this->sitio?->zona_horaria ?? config('plataforma.zona_por_defecto');
    }

    // Desfase en texto para mostrarlo: "UTC−5 h", "UTC+1 h", "UTC−5.5 h"
    public function desfaseTexto(): string
    {
        $horas = $this->desfase_s / 3600;

        return 'UTC' . ($horas < 0 ? '−' : '+') . abs($horas) . ' h';
    }
}
