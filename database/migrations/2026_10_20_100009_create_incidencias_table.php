<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Incidencias: tramas rechazadas (400, 403) o en conflicto (409), con la trama original.
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('incidencias', function (Blueprint $table) {
            $table->id();
            $table->string('tipo', 20);                             // rechazada | conflicto
            $table->string('motivo', 255);
            $table->foreignId('nodo_id')->nullable()->constrained('nodos')->nullOnDelete();
            $table->string('codigo_nodo', 50)->nullable();          // lo que puso la trama, aunque el nodo no exista
            $table->unsignedBigInteger('seq')->nullable();
            $table->string('origen', 20);
            $table->dateTime('recibido_en');                        // UTC
            $table->text('cuerpo_original');
            $table->boolean('revisada')->default(false);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('incidencias');
    }
};
