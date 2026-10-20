<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Sondas: el aparato concreto conectado a un nodo y desde cuándo.
// "fuente" es lo que va antes del primer "_" en la trama (OD, pH, NPK...).
// Una sonda en uso tiene instalada_hasta vacío.
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sondas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('nodo_id')->constrained('nodos')->restrictOnDelete();
            $table->string('fuente', 50);
            $table->string('modelo', 100)->nullable();
            $table->string('numero_serie', 100)->nullable();
            $table->dateTime('instalada_desde')->nullable();
            $table->dateTime('instalada_hasta')->nullable();
            $table->date('ultima_calibracion')->nullable();
            $table->text('notas')->nullable();
            $table->timestamps();

            $table->index(['nodo_id', 'fuente']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sondas');
    }
};
