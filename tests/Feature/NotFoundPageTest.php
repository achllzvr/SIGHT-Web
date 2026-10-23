<?php

namespace Tests\Feature;

use Tests\TestCase;

class NotFoundPageTest extends TestCase
{
    public function test_web_missing_page_shows_branded_404(): void
    {
        $this->get('/totally-missing-page')
            ->assertNotFound()
            ->assertSee("We can't find that page", false);
    }

    public function test_api_missing_route_stays_json(): void
    {
        $this->getJson('/api/totally-missing')
            ->assertNotFound()
            ->assertJson(['message' => 'Not Found']);
    }
}
