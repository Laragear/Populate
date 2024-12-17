<?php

namespace Tests\Pipelines;

use Illuminate\Contracts\Container\Container;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Database\ConnectionResolverInterface;
use Illuminate\Database\Console\Seeds\SeedCommand;
use Illuminate\Support\Collection;
use Laragear\Populate\Pipes\MayWrapStepsIntoTransactions;
use Laragear\Populate\Populator;
use Laragear\Populate\Seeder;
use Laragear\Populate\Seeding;
use Tests\Fixtures\EmptySeeder;
use Tests\TestCase;

class MayWrapStepsIntoTransactionsTest extends TestCase
{
    public function test_doesnt_wraps_into_transactions_if_not_using_transactions(): void
    {
        $this->app->make(Populator::class)->useTransactions = false;

        $container = $this->mock(Container::class);
        $container->expects('make')->never();

        $passable = new Seeding($container, $this->mock(SeedCommand::class), $this->mock(Seeder::class), []);

        $this->app->make(MayWrapStepsIntoTransactions::class)
            ->handle($passable, function (Seeding $seeding) {
                //
            });
    }

    public function test_wraps_into_transactions_using_command_connection(): void
    {
        $this->app->make(Populator::class)->useTransactions = true;

        $stepCalled = false;
        $transactionCalled = false;

        $connection = $this->mock(ConnectionInterface::class);
        $connection->expects('transaction')->andReturnUsing(function ($value) use (&$transactionCalled) {
            $transactionCalled = true;
            return $value();
        });

        $resolver = $this->mock(ConnectionResolverInterface::class);
        $resolver->expects('connection')->with('test-connection')->andReturn($connection);

        $steps = new Collection([
            function () use (&$stepCalled) {
                $stepCalled = true;
            }
        ]);

        $command = $this->mock(SeedCommand::class);
        $command->expects('option')->with('database')->andReturn('test-connection');

        $passable = new Seeding($this->app, $command, new EmptySeeder(), [], $steps);

        $this->app->make(MayWrapStepsIntoTransactions::class)
            ->handle($passable, function (Seeding $seeding) {
                $seeding->steps->each(fn ($callback) => $callback());
            });

        static::assertTrue($stepCalled);
        static::assertTrue($transactionCalled);
    }

    public function test_wraps_using_default_connection(): void
    {
        $this->app->make(Populator::class)->useTransactions = true;

        $stepCalled = false;
        $transactionCalled = false;

        $connection = $this->mock(ConnectionInterface::class);
        $connection->expects('transaction')->andReturnUsing(function ($value) use (&$transactionCalled) {
            $transactionCalled = true;
            return $value();
        });

        $resolver = $this->mock(ConnectionResolverInterface::class);
        $resolver->expects('connection')->with('test-connection')->andReturn($connection);

        $steps = new Collection([
            function () use (&$stepCalled) {
                $stepCalled = true;
            }
        ]);

        $command = $this->mock(SeedCommand::class);
        $command->expects('option')->with('database')->andReturnNull();

        $this->app->make('config')->set('database.default', 'test-connection');

        $passable = new Seeding($this->app, $command, new EmptySeeder(), [], $steps);

        $this->app->make(MayWrapStepsIntoTransactions::class)
            ->handle($passable, function (Seeding $seeding) {
                $seeding->steps->each(fn ($callback) => $callback());
            });

        static::assertTrue($stepCalled);
        static::assertTrue($transactionCalled);
    }
}
