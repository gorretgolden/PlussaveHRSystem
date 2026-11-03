<?php

namespace Tests\Feature;

use Tests\TestCase;

class ExampleTest extends TestCase
{
    public function test_the_root_url_redirects_guests_to_the_sign_in_page(): void
    {
        $this->get('/')->assertRedirect('/login');
    }
}
