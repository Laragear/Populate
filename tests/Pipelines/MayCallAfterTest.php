<?php

namespace Tests\Pipelines;

use Illuminate\Container\Container;
use Illuminate\Database\Console\Seeds\SeedCommand;
use Laragear\Populate\Pipes\MayCallAfter;
use Laragear\Populate\Seeder;
use Laragear\Populate\Seeding;
use Tests\Fixtures\EmptySeeder;
use Tests\TestCase;

class MayCallAfterTest extends TestCase
{
    public function test_doesnt_call_after_if_doesnt_exists(): void
    {
        $seeder = $this->mock(EmptySeeder::class);
        $seeder->expects('after')->never();

        $passable = new Seeding($this->app, $this->mock(SeedCommand::class), $seeder, []);

        $this->app->make(MayCallAfter::class)
            ->handle($passable, function (Seeding $seeding) {

            });
    }

    public function test_calls_after_if_exists(): void
    {
        $seeder = new class extends Seeder
        {
            public bool $run = false;

            public function after(): void
            {
                $this->run = true;
            }
        };


        $container = $this->mock(Container::class);
        $container->expects('call')->with([$seeder, 'after'])->andReturnUsing(fn ($callback) => $callback());

        $passable = new Seeding($container, $this->mock(SeedCommand::class), $seeder, []);

        $this->app->make(MayCallAfter::class)
            ->handle($passable, function (Seeding $seeding) {
                static::assertTrue($seeding->seeder->run);
            });
    }
}
