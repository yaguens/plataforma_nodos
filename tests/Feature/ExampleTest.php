<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    use RefreshDatabase;

    // Seguridad: solo con la base de datos en memoria (RefreshDatabase borraría tus tablas de trabajo)
    protected function beforeRefreshingDatabase()
    {
        if (config('database.default') !== 'sqlite' || config('database.connections.sqlite.database') !== ':memory:') {
            $this->markTestSkipped('Las pruebas necesitan DB_CONNECTION=sqlite y DB_DATABASE=:memory: en phpunit.xml');
        }
    }

    // La portada (panel) se abre aunque no haya ningún nodo todavía
    public function test_el_panel_se_abre_sin_datos(): void
    {
        $this->get('/')->assertStatus(200)->assertSee('Panel de nodos');
    }
}
