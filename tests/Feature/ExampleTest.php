<?php

namespace Tests\Feature;

use Tests\TestCase;

class ExampleTest extends TestCase
{
    public function test_tamu_dialihkan_ke_halaman_login(): void
    {
        $this->get('/')->assertRedirect();
    }
}