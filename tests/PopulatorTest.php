<?php

namespace Tests;

use Illuminate\Contracts\Console\Kernel;
use Laragear\Populate\ContinueData;
use Laragear\Populate\Pipes\FindInvokedSeeder;
use Laragear\Populate\Pipes\FindSeedSteps;
use Laragear\Populate\Pipes\MayCallAfter;
use Laragear\Populate\Pipes\MaySkipSeeder;
use Laragear\Populate\Pipes\MayWrapStepsIntoTransactions;
use Laragear\Populate\Pipes\RemoveContinueDataAndFile;
use Laragear\Populate\Pipes\RunSeedSteps;
use Laragear\Populate\Pipes\WrapSeedSteps;
use Laragear\Populate\Populator;
use ReflectionObject;
use Tests\Fixtures\VariedSeeder;

class PopulatorTest extends TestCase
{
    protected function populator(): Populator
    {
        return $this->app->make(Populator::class);
    }

    public function test_sets_transactions_only_one_time(): void
    {
        static::assertNull($this->populator()->useTransactions);

        $this->populator()->setUseTransactions(true);

        static::assertTrue($this->populator()->useTransactions);

        $this->populator()->setUseTransactions(false);

        static::assertTrue($this->populator()->useTransactions);
    }

    public function test_ensure_pipeline_order(): void
    {
        $populator = $this->populator();

        static::assertSame([
            FindInvokedSeeder::class,
            FindSeedSteps::class,
            WrapSeedSteps::class,
            MayWrapStepsIntoTransactions::class,
            MaySkipSeeder::class,
            RunSeedSteps::class,
            MayCallAfter::class,
            RemoveContinueDataAndFile::class,
        ], (new ReflectionObject($populator))->getProperty('pipes')->getValue($populator));
    }

    public function test_runs_all_pipes(): void
    {
        $seeder = $this->app->instance(VariedSeeder::class, new VariedSeeder());

        $this->app->make(Kernel::class)->call('db:seed', ['--continue' => true, '--class' => VariedSeeder::class]);

        static::assertSame([
            VariedSeeder::class.'::seed',
            VariedSeeder::class.'::seedSecond',
            VariedSeeder::class.'::withAttribute',
        ], $seeder->ran);
    }

    public function test_skips_some_steps(): void
    {
        $seeder = $this->app->instance(VariedSeeder::class, new VariedSeeder());
        $this->app->make(ContinueData::class)->continue = [
            VariedSeeder::class => ['seedSecond' => true],
        ];

        $this->app->make(Kernel::class)->call('db:seed', ['--continue' => true, '--class' => VariedSeeder::class]);

        static::assertSame([
            VariedSeeder::class.'::seed',
            VariedSeeder::class.'::withAttribute',
        ], $seeder->ran);
    }
}
