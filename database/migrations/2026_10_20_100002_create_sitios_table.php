<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Sitios: lugares físicos con nodos (Muelle de Panajachel, PTAR Los Cebollales, Lab Lima...)
// La zona horaria sirve para MOSTRAR las horas del sitio (todo se guarda en UTC).
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sitios', function (Blueprint $table) {
            $table->id();
            $table->foreignId('proyecto_id')->constrained('proyectos')->restrictOnDelete();
            $table->string('nombre', 100);
            $table->string('tipo', 20)->default('otro');                  // lago | ptar | laboratorio | otro
            $table->string('pais', 60)->nullable();
            $table->string('zona_horaria', 64)->default('America/Lima');  // America/Guatemala, Europe/Madrid...
            $table->decimal('latitud', 9, 6)->nullable();
            $table->decimal('longitud', 9, 6)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sitios');
    }
};
