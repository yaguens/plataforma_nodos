<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Ciclos: cada trama recibida, tal cual. La clave de un ciclo es (nodo, seq).
// medido_en y recibido_en van en UTC; ts es el número que mandó el nodo (su hora local).
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ciclos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('nodo_id')->constrained('nodos')->restrictOnDelete();
            $table->unsignedBigInteger('seq')->nullable();          // vacío si el nodo mandó seq = 0
            $table->unsignedBigInteger('ts');
            $table->unsignedInteger('rtc');
            $table->unsignedInteger('formato')->nullable();
            $table->dateTime('medido_en')->nullable();              // UTC; vacío si el nodo no tenía hora
            $table->dateTime('recibido_en');                        // UTC
            $table->string('origen', 20);                           // red | lote | sd | simulador
            $table->foreignId('via_nodo_id')->nullable()->constrained('nodos')->nullOnDelete();   // maestro que la reenvió
            $table->string('marcas', 255)->nullable();              // sin_hora, reloj_desajustado...
            $table->text('cuerpo_original');                        // el JSON tal como llegó

            $table->unique(['nodo_id', 'seq']);
            $table->index(['nodo_id', 'medido_en']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ciclos');
    }
};
