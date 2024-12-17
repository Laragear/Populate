<?php

namespace Laragear\Populate\Pipes;

use Closure;
use Illuminate\Support\Str;
use Laragear\Populate\Seeding;

/**
 * @internal
 */
class FindInvokedSeeder
{
    /**
     * Handle the incoming seeding.
     */
    public function handle(Seeding $seeding, Closure $next): mixed
    {
        if (!$seeding->command) {
            $seeding->class = 'DatabaseSeeder';
        } else {
            $seeding->class = $seeding->command->argument('class') ?? $seeding->command->option('class');

            if (! Str::contains($seeding->class, '\\')) {
                $seeding->class = 'Database\\Seeders\\'.$seeding->class;
            }

            if ($seeding->class === 'Database\\Seeders\\DatabaseSeeder' && ! class_exists($seeding->class)) {
                $seeding->class = 'DatabaseSeeder';
            }
        }

        return $next($seeding);
    }
}
