<?php

namespace Tests\Pipelines;

use Illuminate\Database\Console\Seeds\SeedCommand;
use Laragear\Populate\Pipes\FindSeedSteps;
use Laragear\Populate\Seeding;
use RuntimeException;
use Tests\Fixtures\EmptySeeder;
use Tests\Fixtures\VariedSeeder;
use Tests\TestCase;

class FindSeedStepsTest extends TestCase
{
    public function test_throws_if_no_seed_steps_found(): void
    {
        $passable = new Seeding($this->app, $this->mock(SeedCommand::class), new EmptySeeder(), []);

        $pipe = $this->app->make(FindSeedSteps::class);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('The Tests\Fixtures\EmptySeeder has no Seed Steps.');

        $pipe->handle($passable, function (Seeding $seeding) {
            //
        });
    }

    public function test_finds_public_methods_starting_with_seed(): void
    {
        $passable = new Seeding($this->app, $this->mock(SeedCommand::class), new VariedSeeder(), []);

        $pipe = $this->app->make(FindSeedSteps::class);

        $pipe->handle($passable, function (Seeding $seeding) {
            static::assertCount(3, $seeding->steps);
            static::assertSame('seed', $seeding->steps[0]->name);
            static::assertSame('seedSecond', $seeding->steps[1]->name);
            static::assertSame('withAttribute', $seeding->steps[2]->name);
        });
    }
}
