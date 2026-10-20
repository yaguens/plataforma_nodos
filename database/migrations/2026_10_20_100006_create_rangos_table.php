<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Rangos: qué valores son buenos. Van por TIPO de sitio (lago, ptar...) y, si hace falta,
// por un sitio concreto (que manda sobre el de su tipo). Un límite vacío = sin límite por ese lado.
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rangos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('parametro_id')->constrained('parametros')->cascadeOnDelete();
            $table->string('tipo_sitio', 20)->nullable();
            $table->foreignId('sitio_id')->nullable()->constrained('sitios')->cascadeOnDelete();
            $table->decimal('optimo_min', 12, 4)->nullable();
            $table->decimal('optimo_max', 12, 4)->nullable();
            $table->decimal('aceptable_min', 12, 4)->nullable();
            $table->decimal('aceptable_max', 12, 4)->nullable();
            $table->string('fuente', 150)->nullable();                // de dónde sale el rango
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rangos');
    }
};
