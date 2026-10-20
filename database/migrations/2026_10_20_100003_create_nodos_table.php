<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Nodos: cada placa que envía datos. Se dan de alta solos con su primera trama (sin sitio).
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('nodos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sitio_id')->nullable()->constrained('sitios')->nullOnDelete();
            $table->string('codigo', 20)->unique();                       // 3 últimos bytes de la MAC (AC3D04) o SINID
            $table->string('nombre', 100);
            $table->string('rol', 20)->default('independiente');          // maestro | esclavo | independiente
            $table->foreignId('maestro_id')->nullable()->constrained('nodos')->nullOnDelete();
            $table->integer('desfase_s')->default(-18000);                // hora del nodo respecto a UTC (Lima −5 h, Guatemala −6 h)
            $table->string('clave_api', 64)->nullable();                  // para cuando se active la cabecera X-API-Key
            $table->boolean('activo')->default(true);                     // desactivado → la API responde 403
            $table->unsignedBigInteger('ultimo_seq')->nullable();
            $table->dateTime('ultima_conexion')->nullable();              // UTC
            $table->dateTime('primer_contacto')->nullable();              // UTC
            $table->string('descripcion', 255)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('nodos');
    }
};
