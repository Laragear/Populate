<?php

namespace Laragear\Populate\Pipes;

use Closure;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Filesystem\Filesystem;
use Laragear\Populate\ContinueData;
use Laragear\Populate\Populator;
use Laragear\Populate\Seeding;
use TypeError;

/**
 * @internal
 */
class MayLoadPreviousSeeding
{
    /**
     * Create a new May Load Previous Seeding instance.
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
        if ($this->shouldLoadContinueData($seeding)) {
            $this->loadContinueData($seeding);

            if ($this->data->continue) {
                $this->outputContinuation($seeding);
            }
        }

        return $next($seeding);
    }

    /**
     * Check if the seeding data is not loaded and the command requires it.
     */
    protected function shouldLoadContinueData(Seeding $seeding): bool
    {
        return (bool) $seeding->command?->option('continue');
    }

    /**
     * Load the "continue" data to the populator.
     */
    protected function loadContinueData(Seeding $seeding): void
    {
        $file = $this->continueFilePath($seeding);

        if ($this->files->exists($file) && $this->files->isFile($file)) {
            $this->data->continue = $this->files->json($file);
        }
    }

    /**
     * Returns the path where the "continue" data for the seeder should be.
     */
    protected function continueFilePath(Seeding $seeding): string
    {
        if ($seeding->container instanceof Application) {
            return $seeding->container->storagePath(Populator::STORAGE_PATH.'/'.$seeding->classFileName().'.json');
        }

        throw new TypeError('The container must be an instance of Illuminate\Contracts\Foundation\Application.');
    }

    /**
     * Outputs to the console the continuation of a previous incomplete seeding operation.
     */
    protected function outputContinuation(Seeding $seeding): void
    {
        $seeding->comment('Continuing from previous incomplete seeding.');
    }
}
