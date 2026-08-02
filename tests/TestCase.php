<?php

namespace Tests;

use Tests\Support\SightTestSchema;

abstract class TestCase extends \Illuminate\Foundation\Testing\TestCase
{
    use SightTestSchema;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpSightSchema();
    }
}
