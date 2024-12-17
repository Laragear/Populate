<?php

namespace Tests;

use Illuminate\Console\Command;
use Laragear\Populate\Exceptions\SkipSeeding;
use Laragear\Populate\Populator;
use Laragear\Populate\Seeder;
use Laragear\Populate\Seeding;
use Mockery\MockInterface;

class SeederTest extends TestCase
{
    public function test_skip_throws_exception(): void
    {
        $class = new class extends Seeder
        {
            //
        };

        $this->expectException(SkipSeeding::class);
        $this->expectExceptionMessage('');

        $class->skip();
    }

    public function test_skip_throws_exception_with_reason(): void
    {
        $class = new class extends Seeder
        {
            //
        };

        $this->expectException(SkipSeeding::class);
        $this->expectExceptionMessage('test reason');

        $class->skip('test reason');
    }

    public function test_with_run_calls_native_run(): void
    {
        $class = new class extends Seeder
        {
            public bool $hasRun = false;

            public function run(): void
            {
                $this->hasRun = true;
            }
        };

        $class->__invoke();

        static::assertTrue($class->hasRun);
    }

    public function test_without_run_uses_populator(): void
    {
        $command = $this->mock(Command::class);

        $seeder = new class extends Seeder
        {
            public bool $hasRun = false;

            public function seed(): void
            {
                $this->hasRun = true;
            }
        };

        $this->mock(Populator::class, function (MockInterface $mock) use ($command, $seeder): void {
            $mock->expects('setContainer')->with($this->app)->andReturnSelf();
            $mock->expects('setUseTransactions')->with(true)->andReturnSelf();
            $mock->expects('send')->withArgs(function (Seeding $seeding) use ($seeder, $command): bool {
                static::assertSame($this->app, $seeding->container);
                static::assertSame($command, $seeding->command);
                static::assertSame($seeder, $seeding->seeder);
                static::assertSame(['foo' => 'bar'], $seeding->parameters);

                return true;
            })->andReturnSelf();
            $mock->expects('thenReturn');
        });

        $seeder->setCommand($command);
        $seeder->setContainer($this->app);

        $seeder->__invoke(['foo' => 'bar']);
    }

    public function test_uses_transactions_with_command_option_continue(): void
    {
        $command = $this->mock(Command::class, static function (MockInterface $mock): void {
            $mock->expects('option')->with('continue')->andReturnTrue();
        });

        $seeder = new class extends Seeder
        {
            public bool $useTransactions = false;

            public function seed(): void
            {
                //
            }
        };

        $this->mock(Populator::class, function (MockInterface $mock) use ($command, $seeder): void {
            $mock->expects('setContainer')->andReturnSelf();
            $mock->expects('setUseTransactions')->andReturnSelf();
            $mock->expects('send')->andReturnSelf();
            $mock->expects('thenReturn');
        });

        $seeder->setCommand($command);
        $seeder->setContainer($this->app);

        $seeder->__invoke();
    }
}
