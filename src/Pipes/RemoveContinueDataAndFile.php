<?php

namespace Laragear\Populate\Pipes;

use Closure;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Filesystem\Filesystem;
use Laragear\Populate\ContinueData;
use Laragear\Populate\Populator;
use Laragear\Populate\Seeding;

class RemoveContinueDataAndFile
{
    /**
     * Create a new May Load Previous Seeding instance.
     */
    public function __construct(protected Filesystem $files, protected ContinueData $data)
    {
        //
    }

    /**
     * Handle the incoming seeding.
     */
    public function handle(Seeding $seeding, Closure $next): mixed
    {
        if ($seeding->container instanceof Application) {
            $this->files->delete(
                $seeding->container->storagePath(Populator::STORAGE_PATH . '/' . $seeding->classFileName() . '.json')
            );
        }

        $this->data->continue = [];

        return $next($seeding);
    }
}
