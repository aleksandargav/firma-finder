<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    public function test_the_application_redirects_to_the_company_catalog(): void
    {
        $response = $this->get('/');

        $response->assertRedirect(route('companies.index'));
    }
}
