<?php

namespace Tests\Pipelines;

use Illuminate\Console\View\Components\Factory;
use Illuminate\Contracts\Container\Container;
use Illuminate\Database\Console\Seeds\SeedCommand;
use Illuminate\Support\Fluent;
use Laragear\Populate\Pipes\MaySkipSeeder;
use Laragear\Populate\Seeder;
use Laragear\Populate\Seeding;
use Mockery\MockInterface;
use Orchestra\Testbench\TestCase;
use Tests\Fixtures\EmptySeeder;

class MaySkipSeederTest extends TestCase
{
    public function test_doesnt_skip_if_before_doesnt_exist(): void
    {
        $seeder = $this->mock(Seeder::class);
        $seeder->expects('before')->never();

        $passable = new Seeding($this->app, $this->mock(SeedCommand::class), $seeder, []);

        $this->app->make(MaySkipSeeder::class)
            ->handle($passable, function (Seeding $seeding) {
                //
            });
    }

    public function test_doesnt_skips_if_before_doesnt_call_skip(): void
    {
        $seeder = new class extends Seeder {
            public bool $run = false;

            public function before()
            {
                $this->run = true;
            }
        };

        $container = $this->mock(Container::class);
        $container->expects('call')->with([$seeder, 'before'])->andReturnUsing(fn($callback) => $callback());

        $passable = new Seeding($container, $this->mock(SeedCommand::class), $seeder, []);

        $this->app->make(MaySkipSeeder::class)
            ->handle($passable, function (Seeding $seeding) {
                static::assertTrue($seeding->seeder->run);
            });
    }

    public function test_skips(): void
    {
        $seeder = new class extends Seeder {
            public bool $run = false;

            public function before()
            {
                $this->run = true;

                $this->skip();

                $this->run = false;
            }
        };

        $container = $this->mock(Container::class);
        $container->expects('call')->with([$seeder, 'before'])->andReturnUsing(fn($callback) => $callback());

        $command = $this->mock(SeedCommand::class);
        $command->expects('outputComponents')->andReturn(
            $this->mock(Factory::class, function (MockInterface $mock) use ($seeder) {
                $mock->expects('twoColumnDetail')->with( '↳ '.$seeder::class, '<fg=blue;options=bold>SKIPPED</>');
            })
        );

        $passable = new Seeding($container, $command, $seeder, []);

        $this->app->make(MaySkipSeeder::class)
            ->handle($passable, function (Seeding $seeding) {
                static::assertTrue($seeding->seeder->run);
            });
    }

    public function test_skips_with_reason(): void
    {
        $seeder = new class extends Seeder {
            public bool $run = false;

            public function before()
            {
                $this->run = true;

                $this->skip('test reason');

                $this->run = false;
            }
        };

        $container = $this->mock(Container::class);
        $container->expects('call')->with([$seeder, 'before'])->andReturnUsing(fn($callback) => $callback());

        $command = $this->mock(SeedCommand::class);
        $command->expects('outputComponents')->andReturn(
            $this->mock(Factory::class, function (MockInterface $mock) use ($seeder) {
                $mock->expects('twoColumnDetail')->with( '↳ '.$seeder::class, '<fg=blue;options=bold>SKIPPED</>');
            })
        );
        $command->expects('comment')->with('  test reason');

        $passable = new Seeding($container, $command, $seeder, []);

        $this->app->make(MaySkipSeeder::class)
            ->handle($passable, function (Seeding $seeding) {
                static::assertTrue($seeding->seeder->run);
            });
    }
}
