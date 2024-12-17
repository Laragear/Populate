<?php

namespace Laragear\Populate\Pipes;

use Closure;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Laragear\Populate\Attributes\SeedStep;
use Laragear\Populate\Seeder;
use Laragear\Populate\Seeding;
use ReflectionMethod;
use ReflectionObject;
use RuntimeException;
use function get_class;

/**
 * @internal
 */
class FindSeedSteps
{
    /**
     * Handle the incoming seeding.
     */
    public function handle(Seeding $seeding, Closure $next): mixed
    {
        $seeding->steps = $this->getSeedSteps($seeding->seeder);

        if ($seeding->steps->isEmpty()) {
            throw new RuntimeException('The '.$seeding->seeder::class.' has no Seed Steps.');
        }

        return $next($seeding);
    }

    /**
     * Return a Collection of all filtered seed steps.
     *
     * @return \Illuminate\Support\Collection<int, \ReflectionMethod>
     */
    protected function getSeedSteps(Seeder $seeder): Collection
    {
        return Collection::make((new ReflectionObject($seeder))->getMethods(ReflectionMethod::IS_PUBLIC))
            ->filter(static function (ReflectionMethod $method): bool {
                return ! $method->isStatic()
                    && (Str::startsWith($method->name, 'seed') || !empty($method->getAttributes(SeedStep::class)));
            })
            ->values();
    }
}
