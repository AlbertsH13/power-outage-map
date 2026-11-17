<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    public function createApplication()
    {
        if (empty($_ENV['APP_KEY']) && empty($_SERVER['APP_KEY'])) {
            $key = 'base64:'.base64_encode(random_bytes(32));
            $_ENV['APP_KEY'] = $key;
            $_SERVER['APP_KEY'] = $key;
            putenv("APP_KEY={$key}");
        }

        return parent::createApplication();
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();

        if (blank(config('app.key'))) {
            config(['app.key' => 'base64:'.base64_encode(random_bytes(32))]);
        }
    }
}
