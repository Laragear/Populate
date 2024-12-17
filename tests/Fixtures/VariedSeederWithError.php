<?php

namespace Tests\Fixtures;

use Exception;
use Laragear\Populate\Seeder;

class VariedSeederWithError extends Seeder
{
    public array $ran = [];

    public function seedFirst()
    {
        $this->ran[] = __METHOD__;
    }

    public function seedSecondFails()
    {
        throw new Exception('has failed');
    }

    public function seedThird()
    {
        $this->ran[] = __METHOD__;
    }
}
