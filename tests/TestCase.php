<?php

namespace Tests;

use App\Services\OllamaService;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Tests\Mocks\OllamaServiceMock;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->app->instance(OllamaService::class, new OllamaServiceMock());
    }
}
