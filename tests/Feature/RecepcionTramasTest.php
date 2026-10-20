<?php

namespace Tests\Feature;

use App\Models\Ciclo;
use App\Models\Incidencia;
use App\Models\Lectura;
use App\Models\Nodo;
use App\Models\Parametro;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

// ═════════════════════════════════════════════
// Pruebas automáticas de la recepción de tramas (el contrato con los nodos).
// Se ejecutan con:  php artisan test
// Usan una base de datos en memoria que se crea y se borra en cada prueba:
// NO tocan los datos de tu base de datos de trabajo.
// ═════════════════════════════════════════════
class RecepcionTramasTest extends TestCase
{
    use RefreshDatabase;

    // Seguridad: si las pruebas no usan la base de datos en memoria, no se ejecutan
    // (RefreshDatabase borraría tus tablas de trabajo)
    protected function beforeRefreshingDatabase()
    {
        if (config('database.default') !== 'sqlite' || config('database.connections.sqlite.database') !== ':memory:') {
            $this->markTestSkipped('Las pruebas necesitan DB_CONNECTION=sqlite y DB_DATABASE=:memory: en phpunit.xml');
        }
    }

    private ?int $ts = null;

    // Una trama como las del nodo de Gonzalo, con su hora local de Lima (UTC − 5 h).
    // El ts se fija una vez por prueba: dos tramas "iguales" deben ser idénticas.
    private function trama(array $cambios = []): array
    {
        $this->ts ??= time() - 5 * 3600;

        return array_merge([
            'formato' => 1, 'nodo' => 'AC3D04', 'seq' => 216, 'ts' => $this->ts, 'rtc' => 1,
            'OD_est' => 1, 'OD_OD_medida' => 8.82, 'OD_temperatura' => 25.6,
        ], $cambios);
    }

    public function test_una_trama_correcta_se_guarda_y_responde_200(): void
    {
        $trama = $this->trama();

        $this->postJson('/api/v1/medidas', $trama)
            ->assertStatus(200)
            ->assertExactJson(['ok' => true]);

        $nodo = Nodo::where('codigo', 'AC3D04')->firstOrFail();          // se dio de alta solo
        $this->assertSame(1, Ciclo::count());
        $this->assertSame(2, Lectura::count());                          // OD_est no es una medida
        $this->assertSame(2, Parametro::where('estado', 'Pendiente')->count());
        $this->assertSame(216, $nodo->ultimo_seq);

        // La hora se guarda en UTC: ts (hora de Lima) + 5 h
        $ciclo = Ciclo::first();
        $this->assertSame($trama['ts'] + 5 * 3600, $ciclo->medido_en->timestamp);
        $this->assertSame(1, Lectura::first()->codigo_estado);
    }

    public function test_la_misma_trama_repetida_no_se_duplica(): void
    {
        $this->postJson('/api/v1/medidas', $this->trama())->assertStatus(200);
        $this->postJson('/api/v1/medidas', $this->trama())->assertStatus(200);

        $this->assertSame(1, Ciclo::count());
        $this->assertSame(2, Lectura::count());
    }

    public function test_mismo_seq_con_otro_contenido_es_un_conflicto_409(): void
    {
        $this->postJson('/api/v1/medidas', $this->trama())->assertStatus(200);
        $this->postJson('/api/v1/medidas', $this->trama(['OD_OD_medida' => 5.10]))->assertStatus(409);

        $this->assertSame(1, Ciclo::count());
        $this->assertSame('conflicto', Incidencia::firstOrFail()->tipo);
    }

    public function test_una_trama_sin_seq_se_rechaza_con_400(): void
    {
        $trama = $this->trama();
        unset($trama['seq']);

        $this->postJson('/api/v1/medidas', $trama)->assertStatus(400);

        $this->assertSame(0, Ciclo::count());
        $this->assertSame('rechazada', Incidencia::firstOrFail()->tipo);
    }

    public function test_un_cuerpo_que_no_es_json_se_rechaza_con_400(): void
    {
        $this->call('POST', '/api/v1/medidas', [], [], [], ['CONTENT_TYPE' => 'application/json'], 'esto no es json')
            ->assertStatus(400);
    }

    public function test_un_nodo_desactivado_recibe_403(): void
    {
        Nodo::create(['codigo' => 'AC3D04', 'nombre' => 'Nodo de pruebas', 'activo' => false]);

        $this->postJson('/api/v1/medidas', $this->trama())->assertStatus(403);

        $this->assertSame(0, Ciclo::count());
    }

    public function test_un_sensor_que_falla_guarda_el_valor_vacio_y_su_codigo(): void
    {
        $this->postJson('/api/v1/medidas', $this->trama(['OD_est' => 2, 'OD_OD_medida' => null, 'OD_temperatura' => null]))
            ->assertStatus(200);

        $this->assertSame(2, Lectura::whereNull('valor')->where('codigo_estado', 2)->count());
    }

    public function test_el_lote_procesa_cada_trama_por_separado(): void
    {
        $this->postJson('/api/v1/medidas', $this->trama(['seq' => 2]))->assertStatus(200);

        $lote = [
            $this->trama(['seq' => 1]),                          // nueva → 200
            $this->trama(['seq' => 2]),                          // ya estaba → 200
            $this->trama(['seq' => 2, 'OD_OD_medida' => 1.0]),   // conflicto → 409
            ['nodo' => 'AC3D04'],                                // mal formada → 400
        ];

        $this->postJson('/api/v1/medidas/lote', $lote)
            ->assertStatus(200)
            ->assertExactJson(['resultados' => [
                ['seq' => 1, 'codigo' => 200],
                ['seq' => 2, 'codigo' => 200],
                ['seq' => 2, 'codigo' => 409],
                ['seq' => null, 'codigo' => 400],
            ]]);

        $this->assertSame(2, Ciclo::count());
    }

    public function test_la_pagina_de_tramas_se_muestra(): void
    {
        $this->postJson('/api/v1/medidas', $this->trama())->assertStatus(200);

        $this->get('/tramas')->assertStatus(200)->assertSee('AC3D04');
    }

    public function test_el_panel_muestra_el_nodo_y_sus_valores(): void
    {
        $this->postJson('/api/v1/medidas', $this->trama())->assertStatus(200);

        $this->get('/')->assertStatus(200)
            ->assertSee('AC3D04')
            ->assertSee('Sin sitio asignado')
            ->assertSee('8.82');
    }

    public function test_la_vista_del_nodo_se_muestra_en_cada_periodo(): void
    {
        $this->postJson('/api/v1/medidas', $this->trama())->assertStatus(200);
        $this->postJson('/api/v1/medidas', $this->trama(['seq' => 217, 'OD_est' => 2, 'OD_OD_medida' => null, 'OD_temperatura' => null]))
            ->assertStatus(200);
        $nodo = Nodo::where('codigo', 'AC3D04')->firstOrFail();

        foreach ([6, 24, 168] as $horas) {
            $this->get('/nodos/' . $nodo->id . '?horas=' . $horas)->assertStatus(200)
                ->assertSee('OD_OD_medida')
                ->assertSee('id="grafica-', false);
        }
    }
}
