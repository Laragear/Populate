<?php

namespace Laragear\Populate\Pipes;

use Closure;
use Laragear\Populate\Exceptions\SkipSeeding;
use Laragear\Populate\Seeding;

/**
 * @internal
 */
class MaySkipSeeder
{
    /**
     * Handle the incoming seeding.
     */
    public function handle(Seeding $seeding, Closure $next): mixed
    {
        $shouldSkip = $this->callBefore($seeding);

        return $shouldSkip ? $seeding : $next($seeding);
    }

    /**
     * Execute the "before" method and return if the seeder should be skipped.
     */
    protected function callBefore(Seeding $seeding): bool
    {
        if (method_exists($seeding->seeder, 'before')) {
            try {
                $seeding->container->call([$seeding->seeder, 'before']);
            } catch (SkipSeeding $e) {
                $this->outputSkipped($seeding, $e);

                return true;
            }
        }

        return false;
    }

    /**
     * Output the seeder has been skipped.
     */
    protected function outputSkipped(Seeding $seeding, SkipSeeding $e): void
    {
        $seeding->twoColumn('~ '.$seeding->seeder::class, '<fg=blue;options=bold>SKIPPED</>');

        if ($e->getMessage()) {
            $seeding->comment("  {$e->getMessage()}");
        }
    }
}
