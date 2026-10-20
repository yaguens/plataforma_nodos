<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Lecturas: un valor medido (la tabla que crece). Guarda también el sitio y la sonda
// del momento, para que el histórico no cambie si el nodo se mueve o se cambia la sonda.
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lecturas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ciclo_id')->constrained('ciclos')->cascadeOnDelete();
            $table->foreignId('nodo_id')->constrained('nodos')->restrictOnDelete();
            $table->foreignId('sitio_id')->nullable()->constrained('sitios')->nullOnDelete();
            $table->foreignId('parametro_id')->constrained('parametros')->restrictOnDelete();
            $table->foreignId('sonda_id')->nullable()->constrained('sondas')->nullOnDelete();
            $table->double('valor')->nullable();                    // vacío si el sensor falló
            $table->unsignedInteger('codigo_estado')->nullable();   // <fuente>_est de la trama (1 = OK)
            $table->dateTime('medido_en')->nullable();              // UTC (copia del ciclo, para consultar rápido)
            $table->string('marca', 30)->nullable();                // fuera_escala, no_numerico...

            $table->index(['nodo_id', 'parametro_id', 'medido_en']);
            $table->index(['sitio_id', 'medido_en']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lecturas');
    }
};
