<?php

namespace Tests\Pipelines;

use Exception;
use Illuminate\Container\Container;
use Illuminate\Database\Console\Seeds\SeedCommand;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Collection;
use Laragear\Populate\ContinueData;
use Laragear\Populate\Pipes\RunSeedSteps;
use Laragear\Populate\Populator;
use Laragear\Populate\Seeder;
use Laragear\Populate\Seeding;
use Mockery\MockInterface;
use RuntimeException;
use Tests\Fixtures\EmptySeeder;
use Tests\TestCase;
use Throwable;
use function json_encode;

class RunSeedStepsTest extends TestCase
{
    public function test_calls_seed_step(): void
    {
        $run = false;

        $steps = new Collection([
            function () use (&$run) {
                $run = true;
            },
        ]);

        $passable = new Seeding($this->app, $this->mock(SeedCommand::class), new EmptySeeder(), [], $steps);

        $this->app->make(RunSeedSteps::class)
            ->handle($passable, function (Seeding $seeding) {
                //
            });

        static::assertTrue($run);
    }

    public function test_calls_seed_step_and_saves_incomplete_seeding(): void
    {
        $this->app->make(Populator::class)->useTransactions = true;

        $this->app->make(ContinueData::class)->continue = $array = ['foo' => ['bar' => true]];

        $run = false;

        $steps = new Collection([
            function () use (&$run) {
                $run = true;

                throw new RuntimeException('test');
            },
        ]);

        $this->mock(Filesystem::class, function (MockInterface $mock) use ($array) {
            $mock->expects('ensureDirectoryExists')
                ->with($this->app->storagePath('framework/seeding'))
                ->andReturnTrue();
            $mock->expects('put')
                ->with($this->app->storagePath('framework/seeding/test.json'), json_encode($array))
                ->andReturnTrue();
        });

        $passable = new Seeding($this->app, $this->mock(SeedCommand::class), new EmptySeeder(), [], $steps, 'test');

        $pipe = $this->app->make(RunSeedSteps::class);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('test');

        try {
            $pipe->handle($passable, function (Seeding $seeding) {
                //
            });
        } catch (Throwable $e) {
            static::assertTrue($run);

            throw $e;
        }
    }

    public function test_calls_seed_step_and_doesnt_save_incomplete_seeding_without_transactions(): void
    {
        $this->app->make(Populator::class)->useTransactions = false;

        $this->app->make(ContinueData::class)->continue = ['foo' => ['bar' => true]];

        $run = false;

        $steps = new Collection([
            function () use (&$run) {
                $run = true;

                throw new RuntimeException('test');
            },
        ]);

        $this->mock(Filesystem::class, function (MockInterface $mock) {
            $mock->expects('ensureDirectoryExists')->never();
            $mock->expects('put')->never();
        });

        $passable = new Seeding($this->app, $this->mock(SeedCommand::class), new EmptySeeder(), [], $steps, 'test');

        $pipe = $this->app->make(RunSeedSteps::class);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('test');

        try {
            $pipe->handle($passable, function (Seeding $seeding) {
                //
            });
        } catch (Throwable $e) {
            static::assertTrue($run);

            throw $e;
        }
    }

    public function test_doesnt_saves_seeding_if_container_not_application_instance(): void
    {
        $this->app->make(Populator::class)->useTransactions = true;

        $this->app->make(ContinueData::class)->continue = ['foo' => ['bar' => true]];

        $run = false;

        $steps = new Collection([
            function () use (&$run) {
                $run = true;

                throw new RuntimeException('test');
            },
        ]);

        $this->mock(Filesystem::class, function (MockInterface $mock) {
            $mock->expects('ensureDirectoryExists')->never();
            $mock->expects('put')->never();
        });

        $passable = new Seeding(
            new Container(), $this->mock(SeedCommand::class), new EmptySeeder(), [], $steps, 'test'
        );

        $pipe = $this->app->make(RunSeedSteps::class);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('test');

        try {
            $pipe->handle($passable, function (Seeding $seeding) {
                //
            });
        } catch (Throwable $e) {
            static::assertTrue($run);

            throw $e;
        }
    }

    public function test_calls_on_error_method_before_throwing(): void
    {
        $this->app->make(Populator::class)->useTransactions = true;

        $this->app->make(ContinueData::class)->continue = $array = ['foo' => ['bar' => true]];

        $run = false;

        $steps = new Collection([
            function () use (&$run) {
                $run = true;

                throw new RuntimeException('test');
            },
        ]);

        $this->mock(Filesystem::class, function (MockInterface $mock) use ($array) {
            $mock->expects('ensureDirectoryExists')
                ->with($this->app->storagePath('framework/seeding'))
                ->andReturnTrue();
            $mock->expects('put')
                ->with($this->app->storagePath('framework/seeding/test.json'), json_encode($array))
                ->andReturnTrue();
        });

        $seeder = new class extends Seeder {
            public $exception;

            public function onError($exception) {
                $this->exception = $exception;
            }
        };

        $passable = new Seeding($this->app, $this->mock(SeedCommand::class), $seeder, [], $steps, 'test');

        $pipe = $this->app->make(RunSeedSteps::class);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('test');

        try {
            $pipe->handle($passable, function (Seeding $seeding) {
                //
            });
        } catch (Throwable $e) {
            static::assertTrue($run);
            static::assertSame($e, $seeder->exception);

            throw $e;
        }
    }

    public function test_on_error_replaces_exception_by_returning_it(): void
    {
        $this->app->make(Populator::class)->useTransactions = true;

        $this->app->make(ContinueData::class)->continue = $array = ['foo' => ['bar' => true]];

        $run = false;

        $steps = new Collection([
            function () use (&$run) {
                $run = true;

                throw new RuntimeException('test');
            },
        ]);

        $this->mock(Filesystem::class, function (MockInterface $mock) use ($array) {
            $mock->expects('ensureDirectoryExists')
                ->with($this->app->storagePath('framework/seeding'))
                ->andReturnTrue();
            $mock->expects('put')
                ->with($this->app->storagePath('framework/seeding/test.json'), json_encode($array))
                ->andReturnTrue();
        });

        $seeder = new class extends Seeder {
            public function onError($exception) {
                return new Exception('replacement', previous: $exception);
            }
        };

        $passable = new Seeding($this->app, $this->mock(SeedCommand::class), $seeder, [], $steps, 'test', $array);

        $pipe = $this->app->make(RunSeedSteps::class);

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('replacement');

        try {
            $pipe->handle($passable, function (Seeding $seeding) {
                //
            });
        } catch (Throwable $e) {
            static::assertTrue($run);
            static::assertSame('test', $e->getPrevious()->getMessage());

            throw $e;
        }
    }

    public function test_on_error_replaces_exception_by_throwing_it(): void
    {
        $this->app->make(Populator::class)->useTransactions = true;

        $this->app->make(ContinueData::class)->continue = $array = ['foo' => ['bar' => true]];

        $run = false;

        $steps = new Collection([
            function () use (&$run) {
                $run = true;

                throw new RuntimeException('test');
            },
        ]);

        $this->mock(Filesystem::class, function (MockInterface $mock) use ($array) {
            $mock->expects('ensureDirectoryExists')
                ->with($this->app->storagePath('framework/seeding'))
                ->andReturnTrue();
            $mock->expects('put')
                ->with($this->app->storagePath('framework/seeding/test.json'), json_encode($array))
                ->andReturnTrue();
        });

        $seeder = new class extends Seeder {
            public function onError($exception) {
                throw new Exception('replacement', previous: $exception);
            }
        };

        $passable = new Seeding($this->app, $this->mock(SeedCommand::class), $seeder, [], $steps, 'test', $array);

        $pipe = $this->app->make(RunSeedSteps::class);

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('replacement');

        try {
            $pipe->handle($passable, function (Seeding $seeding) {
                //
            });
        } catch (Throwable $e) {
            static::assertTrue($run);
            static::assertSame('test', $e->getPrevious()->getMessage());

            throw $e;
        }
    }
}
