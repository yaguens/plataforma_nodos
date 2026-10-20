<?php

namespace Tests\Feature;

use Tests\TestCase;

class ExampleTest extends TestCase
{
    // La portada lleva a la página de tramas
    public function test_la_portada_redirige_a_tramas(): void
    {
        $this->get('/')->assertRedirect('/tramas');
    }
}
