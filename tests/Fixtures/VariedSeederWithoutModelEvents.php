<?php

namespace Tests\Fixtures;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Laragear\Populate\Attributes\SeedStep;
use Laragear\Populate\Seeder;

class VariedSeederWithoutModelEvents extends Seeder
{
    use WithoutModelEvents;

    public array $ran = [];
    protected bool $notEvents = false;

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

    public function withoutModelEvents(callable $callback)
    {
        $this->notEvents = true;

        return $callback;
    }
}
