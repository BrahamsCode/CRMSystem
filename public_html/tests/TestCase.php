<?php

namespace Tests;

use App\Services\Nucleo\Hatuban;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function tearDown(): void
    {
        // Hatuban::fake() es estático: que no pase de una prueba a otra (NUCLEO.md, "Numeración")
        Hatuban::unfake();

        parent::tearDown();
    }
}
