<?php

namespace Tests;

use Laragear\Populate\PopulateServiceProvider;
use Orchestra\Testbench\TestCase as BaseTestCase;

class TestCase extends BaseTestCase
{
    protected function getPackageProviders($app): array
    {
        return [
            PopulateServiceProvider::class
        ];
    }
}
