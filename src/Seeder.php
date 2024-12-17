<?php

namespace Laragear\Populate;

use Illuminate\Database\Seeder as LaravelSeeder;
use function method_exists;

abstract class Seeder extends LaravelSeeder
{
    /**
     * If the seeder should wrap each Seed Step into a database transaction.
     */
    public bool $useTransactions = true;

    /**
     * Skips the current Seed Step, or the whole Seeder if called inside `before()`.
     */
    public function skip(string $reason = ''): never
    {
        throw new Exceptions\SkipSeeding($reason);
    }

    /**
     * Run the database seeds.
     */
    public function __invoke(array $parameters = []): void
    {
        method_exists($this, 'run')
            ? parent::__invoke($parameters)
            : app(Populator::class)
                ->setContainer($this->container) // We need to set the container separately to mock the service.
                ->setUseTransactions($this->useTransactions || $this->command?->option('continue'))
                ->send(new Seeding($this->container, $this->command, $this, $parameters))
                ->thenReturn();
    }
}
