<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Tests tidak bergantung pada hasil "npm run build" (manifest Vite).
        $this->withoutVite();
    }
}
