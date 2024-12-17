<?php

namespace Laragear\Populate\Pipes;

use Closure;
use Illuminate\Database\ConnectionResolverInterface;
use Laragear\Populate\Populator;
use Laragear\Populate\Seeding;

/**
 * @internal
 */
class MayWrapStepsIntoTransactions
{
    /**
     * Create a new May Wrap Steps Into Transactions instance.
     */
    public function __construct(protected Populator $populator)
    {
        //
    }

    /**
     * Handle the incoming seeding.
     *
     * @throws \Illuminate\Contracts\Container\BindingResolutionException
     */
    public function handle(Seeding $seeding, Closure $next): mixed
    {
        if ($this->populator->useTransactions) {
            $transaction = $this->getTransaction($seeding);

            $seeding->steps->transform(function (Closure $callback) use ($transaction): Closure {
                return function () use ($callback, $transaction): void {
                    $transaction($callback);
                };
            });
        }

        return $next($seeding);
    }

    /**
     * Returns the transaction from the database connection from the seeding command.
     *
     * @return \Closure(\Closure): void
     * @throws \Illuminate\Contracts\Container\BindingResolutionException
     */
    protected function getTransaction(Seeding $seeding): Closure
    {
        return $seeding->container->make(ConnectionResolverInterface::class)->connection(
            $seeding->command?->option('database') ?: $seeding->container->make('config')->get('database.default'),
        )->transaction(...);
    }
}
