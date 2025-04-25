<?php

namespace Tests\Pipelines;

use Exception;
use Illuminate\Console\View\Components\Factory;
use Illuminate\Database\Console\Seeds\SeedCommand;
use Illuminate\Database\Eloquent\Factories\Factory as EloquentFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Collection;
use Laragear\Populate\Attributes\SeedStep;
use Laragear\Populate\ContinueData;
use Laragear\Populate\Pipes\WrapSeedSteps;
use Laragear\Populate\Seeder;
use Laragear\Populate\Seeding;
use Mockery\MockInterface;
use ReflectionMethod;
use Tests\Fixtures\NamedSeedStepSeeder;
use Tests\Fixtures\VariedSeeder;
use Tests\Fixtures\VariedSeederSkipsSeedStep;
use Tests\Fixtures\VariedSeederWithError;
use Tests\Fixtures\VariedSeederWithoutModelEvents;
use Tests\Fixtures\VariedSeederWithoutSeedStepEvent;
use Tests\Fixtures\VariedSeederWithParseableReturns;
use Tests\TestCase;
use Throwable;

class WrapSeedStepsTest extends TestCase
{
    public function test_transforms_reflection_method_into_seed_step_call_closure(): void
    {
        $seeder = new VariedSeeder();

        $command = $this->mock(SeedCommand::class, function (MockInterface $mock) {
            $factory = $this->mock(Factory::class, function (MockInterface $mock) {
                $mock->expects('twoColumnDetail')->with("~ Seed", '<fg=green;options=bold>DONE</>');
            });

            $mock->expects('outputComponents')->andReturn($factory);
        });

        $passable = new Seeding($this->app, $command, $seeder, [], new Collection([
            new ReflectionMethod(VariedSeeder::class, 'seed'),
        ]));

        $this->app->make(WrapSeedSteps::class)
            ->handle($passable, function (Seeding $seeding) {
                static::assertIsCallable($seeding->steps[0]);

                $seeding->steps->each(fn($step) => $step());

                static::assertContains(VariedSeeder::class.'::seed', $seeding->seeder->ran);
            });
    }

    public function test_seed_step_skips_if_already_ran(): void
    {
        $this->app->instance(ContinueData::class, new ContinueData([
            VariedSeeder::class => ['seed' => true],
        ]));

        $seeder = new VariedSeeder();

        $command = $this->mock(SeedCommand::class, function (MockInterface $mock) {
            $factory = $this->mock(Factory::class, function (MockInterface $mock) {
                $mock->expects('twoColumnDetail')->with("~ Seed", '<fg=gray;options=bold>CONTINUE</>');
                $mock->expects('twoColumnDetail')->with("~ Seed second", '<fg=green;options=bold>DONE</>');
                $mock->expects('twoColumnDetail')->with("~ With attribute", '<fg=green;options=bold>DONE</>');
            });

            $mock->expects('outputComponents')->times(3)->andReturn($factory);
        });

        $passable = new Seeding($this->app, $command, $seeder, [], new Collection([
            new ReflectionMethod(VariedSeeder::class, 'seed'),
            new ReflectionMethod(VariedSeeder::class, 'seedSecond'),
            new ReflectionMethod(VariedSeeder::class, 'withAttribute'),
        ]));

        $this->app->make(WrapSeedSteps::class)
            ->handle($passable, function (Seeding $seeding) {
                static::assertCount(3, $seeding->steps);

                $seeding->steps->each(fn($step) => $step());

                static::assertCount(2, $seeding->seeder->ran);
                static::assertNotContains(VariedSeeder::class.'::seed', $seeding->seeder->ran);
                static::assertContains(VariedSeeder::class.'::seedSecond', $seeding->seeder->ran);
                static::assertContains(VariedSeeder::class.'::withAttribute', $seeding->seeder->ran);
            });
    }

    public function test_seed_step_skips_outputs_custom_name(): void
    {
        $this->app->instance(ContinueData::class, new ContinueData([
            NamedSeedStepSeeder::class => ['withAttribute' => true],
        ]));

        $seeder = new NamedSeedStepSeeder();

        $command = $this->mock(SeedCommand::class, function (MockInterface $mock) {
            $factory = $this->mock(Factory::class, function (MockInterface $mock) {
                $mock->expects('twoColumnDetail')->with("~ Default seed step name", '<fg=green;options=bold>DONE</>');
                $mock->expects('twoColumnDetail')->with("~ Seed second", '<fg=green;options=bold>DONE</>');
                $mock->expects('twoColumnDetail')->with("~ Custom seed step name", '<fg=gray;options=bold>CONTINUE</>');
            });

            $mock->expects('outputComponents')->times(3)->andReturn($factory);
        });

        $passable = new Seeding($this->app, $command, $seeder, [], new Collection([
            new ReflectionMethod(NamedSeedStepSeeder::class, 'seedSecond'),
            new ReflectionMethod(NamedSeedStepSeeder::class, 'seed'),
            new ReflectionMethod(NamedSeedStepSeeder::class, 'withAttribute'),
        ]));

        $this->app->make(WrapSeedSteps::class)
            ->handle($passable, function (Seeding $seeding) {
                static::assertCount(3, $seeding->steps);

                $seeding->steps->each(fn($step) => $step());

                static::assertCount(2, $seeding->seeder->ran);
                static::assertContains(NamedSeedStepSeeder::class.'::seed', $seeding->seeder->ran);
                static::assertContains(NamedSeedStepSeeder::class.'::seedSecond', $seeding->seeder->ran);
                static::assertNotContains(NamedSeedStepSeeder::class.'::withAttribute', $seeding->seeder->ran);
            });
    }

    public function test_runs_seed_step_without_model_events_by_seeder(): void
    {
        $seeder = new VariedSeederWithoutModelEvents();

        $passable = new Seeding($this->app, null, $seeder, [], new Collection([
            new ReflectionMethod(VariedSeederWithoutModelEvents::class, 'seedSecond'),
            new ReflectionMethod(VariedSeederWithoutModelEvents::class, 'seed'),
            new ReflectionMethod(VariedSeederWithoutModelEvents::class, 'withAttribute'),
        ]));

        $this->app->make(WrapSeedSteps::class)
            ->handle($passable, function (Seeding $seeding) {
                static::assertCount(3, $seeding->steps);

                $seeding->steps->each(fn($step) => $step());

                static::assertCount(3, $seeding->seeder->ran);
                static::assertContains(VariedSeederWithoutModelEvents::class.'::seed', $seeding->seeder->ran);
                static::assertContains(VariedSeederWithoutModelEvents::class.'::seedSecond', $seeding->seeder->ran);
                static::assertContains(VariedSeederWithoutModelEvents::class.'::withAttribute', $seeding->seeder->ran);
            });
    }

    public function test_runs_seed_step_without_model_events_by_attribute(): void
    {
        $seeder = new VariedSeederWithoutSeedStepEvent();

        $passable = new Seeding($this->app, null, $seeder, [], new Collection([
            new ReflectionMethod(VariedSeederWithoutSeedStepEvent::class, 'seedSecond'),
            new ReflectionMethod(VariedSeederWithoutSeedStepEvent::class, 'seed'),
            new ReflectionMethod(VariedSeederWithoutSeedStepEvent::class, 'withAttribute'),
        ]));

        $this->app->make(WrapSeedSteps::class)
            ->handle($passable, function (Seeding $seeding) {
                static::assertCount(3, $seeding->steps);

                $seeding->steps[0]();
                static::assertFalse($seeding->seeder->withoutEvents);

                $seeding->steps[1]();
                static::assertFalse($seeding->seeder->withoutEvents);

                $seeding->steps[2]();
                static::assertTrue($seeding->seeder->withoutEvents);
            });
    }

    public function test_skips_seed_step(): void
    {
        $seeder = new VariedSeederSkipsSeedStep();

        $command = $this->mock(SeedCommand::class, function (MockInterface $mock) {
            $factory = $this->mock(Factory::class, function (MockInterface $mock) {
                $mock->expects('twoColumnDetail')->with("~ Seed", '<fg=green;options=bold>DONE</>');
                $mock->expects('twoColumnDetail')->with("~ Seed second skips", '<fg=blue;options=bold>SKIPPED</>');
                $mock->expects('twoColumnDetail')->with("~ Third skips", '<fg=blue;options=bold>SKIPPED</>');
            });

            $mock->expects('outputComponents')->times(3)->andReturn($factory);
            $mock->expects('comment')->with('  test reason');
        });

        $passable = new Seeding($this->app, $command, $seeder, [], new Collection([
            new ReflectionMethod(VariedSeederSkipsSeedStep::class, 'seed'),
            new ReflectionMethod(VariedSeederSkipsSeedStep::class, 'seedSecondSkips'),
            new ReflectionMethod(VariedSeederSkipsSeedStep::class, 'thirdSkips'),
        ]));

        $this->app->make(WrapSeedSteps::class)
            ->handle($passable, function (Seeding $seeding) {
                static::assertCount(3, $seeding->steps);

                $seeding->steps->each(fn($step) => $step());

                static::assertCount(1, $seeding->seeder->ran);
                static::assertContains(VariedSeederSkipsSeedStep::class.'::seed', $seeding->seeder->ran);
            });
    }

    public function test_seed_step_error_is_added_to_continue(): void
    {
        $seeder = new VariedSeederWithError();

        $command = $this->mock(SeedCommand::class, function (MockInterface $mock) {
            $factory = $this->mock(Factory::class, function (MockInterface $mock) {
                $mock->expects('twoColumnDetail')->with("~ Seed first", '<fg=green;options=bold>DONE</>');
                $mock->expects('twoColumnDetail')->with("! Seed second fails", '<fg=red;options=bold>ERROR</>');
            });

            $mock->expects('outputComponents')->times(2)->andReturn($factory);
        });

        $passable = new Seeding($this->app, $command, $seeder, [], new Collection([
            new ReflectionMethod(VariedSeederWithError::class, 'seedFirst'),
            new ReflectionMethod(VariedSeederWithError::class, 'seedSecondFails'),
            new ReflectionMethod(VariedSeederWithError::class, 'seedThird'),
        ]));

        $pipe = $this->app->make(WrapSeedSteps::class);

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('has failed');

        try {
            $pipe->handle($passable, function (Seeding $seeding) {
                $seeding->steps->each(fn($step) => $step());
            });
        } catch (Throwable $e) {
            static::assertCount(1, $seeder->ran);
            static::assertContains(VariedSeederWithError::class.'::seedFirst', $seeder->ran);

            throw $e;
        }
    }

    public function test_parses_results(): void
    {
        $seeder = new VariedSeederWithParseableReturns(
            $factory = $this->mock(EloquentFactory::class),
            $model = $this->mock(Model::class),
            new Collection([$model]),
        );

        $factory->expects('create');
        $model->expects('push');

        $passable = new Seeding($this->app, null, $seeder, [], new Collection([
            new ReflectionMethod(VariedSeederWithParseableReturns::class, 'seedFactory'),
            new ReflectionMethod(VariedSeederWithParseableReturns::class, 'seedModel'),
            new ReflectionMethod(VariedSeederWithParseableReturns::class, 'seedCollection'),
        ]));

        $this->app->make(WrapSeedSteps::class)
            ->handle($passable, function (Seeding $seeding) {
                $seeding->steps->each(fn($step) => $step());
            });

    }

    public function test_retries_unique_constraint_validation(): void
    {
        $seeder = new class extends Seeder {
            public $ran = 0;

            public function seed()
            {
                $this->ran++;

                throw new UniqueConstraintViolationException('test', 'sql', [], new Exception());
            }
        };

        $command = $this->mock(SeedCommand::class, function (MockInterface $mock) {
            $factory = $this->mock(Factory::class, function (MockInterface $mock) {
                $mock->expects('twoColumnDetail')->with('~ Seed', '<fg=yellow;options=bold>RETRY UNIQUE</>');
                $mock->expects('twoColumnDetail')->with('! Seed', '<fg=red;options=bold>ERROR</>');
            });

            $mock->expects('outputComponents')->twice()->andReturn($factory);
        });

        $passable = new Seeding($this->app, $command, $seeder, [], new Collection([
            new ReflectionMethod($seeder, 'seed'),
        ]));

        $pipe = $this->app->make(WrapSeedSteps::class);

        $this->expectException(UniqueConstraintViolationException::class);

        try {
            $pipe->handle($passable, function (Seeding $seeding) {
                $seeding->steps->each(fn($step) => $step());
            });
        } catch (UniqueConstraintViolationException $e) {
            static::assertSame(2, $seeder->ran);

            throw $e;
        }
    }

    public function test_retries_unique_constraint_validation_continiues_seeding(): void
    {
        $seeder = new class extends Seeder {
            public $ran = 0;

            public function seed()
            {
                $this->ran++;

                if ($this->ran < 2) {
                    throw new UniqueConstraintViolationException('test', 'sql', [], new Exception());
                }
            }
        };

        $command = $this->mock(SeedCommand::class, function (MockInterface $mock) {
            $factory = $this->mock(Factory::class, function (MockInterface $mock) {
                $mock->expects('twoColumnDetail')->with('~ Seed', '<fg=yellow;options=bold>RETRY UNIQUE</>');
                $mock->expects('twoColumnDetail')->with('~ Seed', '<fg=green;options=bold>DONE</>');
                $mock->expects('twoColumnDetail')->with('! Seed', '<fg=red;options=bold>ERROR</>')->never();
            });

            $mock->expects('outputComponents')->times(2)->andReturn($factory);
        });

        $passable = new Seeding($this->app, $command, $seeder, [], new Collection([
            new ReflectionMethod($seeder, 'seed'),
        ]));

        $this->app->make(WrapSeedSteps::class)
            ->handle($passable, function (Seeding $seeding) {
                $seeding->steps->each(fn($step) => $step());
            });

        static::assertSame(2, $seeder->ran);
    }

    public function test_doesnt_retries_unique_constraint_validation_when_transactions_disabled(): void
    {
        $seeder = new class extends Seeder {
            public $ran = 0;

            public bool $useTransactions = false;

            public function seed()
            {
                $this->ran++;

                throw new UniqueConstraintViolationException('test', 'sql', [], new Exception());
            }
        };

        $command = $this->mock(SeedCommand::class, function (MockInterface $mock) {
            $factory = $this->mock(Factory::class, function (MockInterface $mock) {
                $mock->expects('twoColumnDetail')->with('~ Seed', '<fg=yellow;options=bold>RETRY UNIQUE</>')->never();
                $mock->expects('twoColumnDetail')->with('! Seed', '<fg=red;options=bold>ERROR</>');
            });

            $mock->expects('outputComponents')->andReturn($factory);
        });

        $passable = new Seeding($this->app, $command, $seeder, [], new Collection([
            new ReflectionMethod($seeder, 'seed'),
        ]));

        $pipe = $this->app->make(WrapSeedSteps::class);

        $this->expectException(UniqueConstraintViolationException::class);

        try {
            $pipe->handle($passable, function (Seeding $seeding) {
                $seeding->steps->each(fn($step) => $step());
            });
        } catch (UniqueConstraintViolationException $e) {
            static::assertSame(1, $seeder->ran);

            throw $e;
        }
    }

    public function test_retries_unique_constraint_validation_a_number_of_tries(): void
    {
        $seeder = new class extends Seeder {
            public $ran = 0;

            #[SeedStep(retryUnique: 3)]
            public function seed()
            {
                $this->ran++;

                throw new UniqueConstraintViolationException('test', 'sql', [], new Exception());
            }
        };

        $command = $this->mock(SeedCommand::class, function (MockInterface $mock) {
            $factory = $this->mock(Factory::class, function (MockInterface $mock) {
                $mock->expects('twoColumnDetail')->times(3)->with('~ Seed', '<fg=yellow;options=bold>RETRY UNIQUE</>');
                $mock->expects('twoColumnDetail')->with('! Seed', '<fg=red;options=bold>ERROR</>');
            });

            $mock->expects('outputComponents')->times(4)->andReturn($factory);
        });

        $passable = new Seeding($this->app, $command, $seeder, [], new Collection([
            new ReflectionMethod($seeder, 'seed'),
        ]));

        $pipe = $this->app->make(WrapSeedSteps::class);

        $this->expectException(UniqueConstraintViolationException::class);

        try {
            $pipe->handle($passable, function (Seeding $seeding) {
                $seeding->steps->each(fn($step) => $step());
            });
        } catch (UniqueConstraintViolationException $e) {
            static::assertSame(4, $seeder->ran);

            throw $e;
        }
    }

    public function test_doesnt_retry_when_retry_unique_is_false(): void
    {
        $seeder = new class extends Seeder {
            public $ran = 0;

            #[SeedStep(retryUnique: false)]
            public function seed()
            {
                $this->ran++;

                throw new UniqueConstraintViolationException('test', 'sql', [], new Exception());
            }
        };

        $command = $this->mock(SeedCommand::class, function (MockInterface $mock) {
            $factory = $this->mock(Factory::class, function (MockInterface $mock) {
                $mock->expects('twoColumnDetail')->with('~ Seed', '<fg=yellow;options=bold>RETRY UNIQUE</>')->never();
                $mock->expects('twoColumnDetail')->with('! Seed', '<fg=red;options=bold>ERROR</>');
            });

            $mock->expects('outputComponents')->andReturn($factory);
        });

        $passable = new Seeding($this->app, $command, $seeder, [], new Collection([
            new ReflectionMethod($seeder, 'seed'),
        ]));

        $pipe = $this->app->make(WrapSeedSteps::class);

        $this->expectException(UniqueConstraintViolationException::class);

        try {
            $pipe->handle($passable, function (Seeding $seeding) {
                $seeding->steps->each(fn($step) => $step());
            });
        } catch (UniqueConstraintViolationException $e) {
            static::assertSame(1, $seeder->ran);

            throw $e;
        }
    }
}
