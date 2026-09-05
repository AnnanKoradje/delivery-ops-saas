<?php

namespace Tests\Feature\Auth;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_registration_is_unavailable_until_the_controlled_company_onboarding_phase(): void
    {
        $this->get('/register')->assertNotFound();
    }

    public function test_registration_submissions_are_rejected_until_onboarding_is_implemented(): void
    {
        $this->post('/register', [
            'name' => 'Unapproved User',
            'email' => 'unapproved@example.test',
            'password' => 'password',
            'password_confirmation' => 'password',
        ])->assertNotFound();

        $this->assertGuest();
    }
}
