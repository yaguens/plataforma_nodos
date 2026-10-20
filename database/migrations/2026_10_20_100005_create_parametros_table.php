<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Parámetros: catálogo común de magnitudes. Cada clave de la trama (<fuente>_<magnitud>)
// es un parámetro; si llega una clave nueva, se crea sola como "Pendiente".
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('parametros', function (Blueprint $table) {
            $table->id();
            $table->string('codigo', 50)->unique();                // OD_OD_medida, pH_pH...
            $table->string('nombre', 100);
            $table->string('unidad', 20)->nullable();
            $table->unsignedTinyInteger('decimales')->default(2);
            $table->decimal('escala_min', 12, 4)->default(0);       // valores posibles del sensor
            $table->decimal('escala_max', 12, 4)->default(100);
            $table->boolean('mostrar_medidor')->default(true);
            $table->string('estado', 20)->default('Pendiente');    // Pendiente | Activo | Oculto
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('parametros');
    }
};
