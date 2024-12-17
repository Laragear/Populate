<?php

namespace Tests\Fixtures;

use Laragear\Populate\Attributes\SeedStep;
use Laragear\Populate\Seeder;

class NamedSeedStepSeeder extends Seeder
{
    public array $ran = [];

    #[SeedStep(as: 'Default seed step name')]
    public function seed()
    {
        $this->ran[] = __METHOD__;
    }

    public function seedSecond()
    {
        $this->ran[] = __METHOD__;
    }

    #[SeedStep(as: 'Custom seed step name')]
    public function withAttribute()
    {
        $this->ran[] = __METHOD__;
    }
}
