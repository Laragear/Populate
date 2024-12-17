<?php

namespace Tests\Fixtures;

use Laragear\Populate\Attributes\SeedStep;
use Laragear\Populate\Seeder;

class VariedSeederWithoutSeedStepEvent extends Seeder
{
    public array $ran = [];

    public bool $withoutEvents = false;

    #[SeedStep(as: 'Default seed step name')]
    public function seed()
    {
        $this->ran[] = __METHOD__;
    }

    public function seedSecond()
    {
        $this->ran[] = __METHOD__;
    }

    #[SeedStep(as: 'Custom seed step name', withoutModelEvents: true)]
    public function withAttribute()
    {
        $this->withoutEvents = true;
        $this->ran[] = __METHOD__;
    }
}
