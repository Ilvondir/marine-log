<?php

namespace Tests\Feature;

use Tests\TestCase;

class ExampleTest extends TestCase
{
    public function test_homepage_returns_successful_response(): void
    {
        $response = $this->get(route('home'));

        $response->assertStatus(200);
        $response->assertSee('MarineLog');
    }

    public function test_auth_entry_pages_return_successful_response(): void
    {
        $this->get(route('login'))->assertStatus(200)->assertSee('Sign in');
        $this->get(route('register'))->assertStatus(200)->assertSee('Create account');
    }
}
