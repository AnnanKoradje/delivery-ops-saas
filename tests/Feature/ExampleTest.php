<?php

namespace Tests\Feature;

// use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    public function test_the_root_redirects_to_the_secure_dashboard_entry_point(): void
    {
        $this->get('/')->assertRedirect('/dashboard');
    }
}
