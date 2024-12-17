<?php

namespace Tests\Fixtures;

use Laragear\Populate\Seeder;

class VariedSeederSkipsSeedStep extends Seeder
{
    public array $ran = [];

    public function seed()
    {
        $this->ran[] = __METHOD__;
    }

    public function seedSecondSkips()
    {
        $this->skip();

        $this->ran[] = __METHOD__;
    }

    public function thirdSkips()
    {
        $this->skip('test reason');

        $this->ran[] = __METHOD__;
    }
}
