<?php

namespace Laragear\Populate\Pipes;

use Closure;
use Laragear\Populate\Seeding;
use function method_exists;

/**
 * @internal
 */
class MayCallAfter
{
    /**
     * Handle the incoming seeding.
     */
    public function handle(Seeding $seeding, Closure $next): mixed
    {
        if (method_exists($seeding->seeder, 'after')) {
            $seeding->container->call([$seeding->seeder, 'after']);
        }

        return $next($seeding);
    }
}
