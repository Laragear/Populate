<?php

namespace Laragear\Populate\Pipes;

use Closure;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Filesystem\Filesystem;
use Laragear\Populate\ContinueData;
use Laragear\Populate\Populator;
use Laragear\Populate\Seeding;
use Throwable;
use function method_exists;

/**
 * @internal
 */
class RunSeedSteps
{
    /**
     * Create a new Run Seed Step instance.
     */
    public function __construct(
        protected Filesystem $files,
        protected Populator $populator,
        protected ContinueData $data,
    ) {
        //
    }

    /**
     * Handle the incoming seeding.
     */
    public function handle(Seeding $seeding, Closure $next): mixed
    {
        try {
            $seeding->steps->each(static function (Closure $callable): void {
                $callable();
            });
        } catch (Throwable $e) {
            $e = $this->callOnError($seeding, $e);

            if ($this->populator->useTransactions) {
                $this->saveIncompleteSeeding($seeding);
            }

            throw $e;
        }

        return $next($seeding);
    }

    /**
     * Call the "onError" method if the seeder as returned an error.
     */
    protected function callOnError(Seeding $seeding, Throwable $e): Throwable
    {
        if (method_exists($seeding->seeder, 'onError')) {
            try {
                $result = $seeding->seeder->onError($e);
            } catch (Throwable $newException) {
                return $newException;
            }

            if ($result instanceof Throwable) {
                $e = $result;
            }
        }

        return $e;
    }

    /**
     * Save the incomplete seeding operation if it didn't complete.
     */
    protected function saveIncompleteSeeding(Seeding $seeding): void
    {
        if ($seeding->container instanceof Application) {
            $this->files->ensureDirectoryExists($path = $seeding->container->storagePath(Populator::STORAGE_PATH));

            $this->files->put("$path/{$seeding->classFileName()}.json", json_encode($this->data->continue));
        }
    }
}
