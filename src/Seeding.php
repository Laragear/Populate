<?php

namespace Laragear\Populate;

use Illuminate\Console\Command;
use Illuminate\Contracts\Container\Container;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * @internal
 */
class Seeding
{
    /**
     * Create a new Seeding instance.
     *
     * @param  class-string|string  $class
     * @param  \Illuminate\Support\Collection<int, \Closure|\ReflectionMethod>  $steps
     */
    public function __construct(
        public Container $container,
        public ?Command $command,
        public Seeder $seeder,
        public array $parameters,
        public Collection $steps = new Collection(),
        public string $class = '',
    ) {
        //
    }

    /**
     * Returns the class name apt for file name.
     */
    public function classFileName(): string
    {
        return Str::replace('\\', '_', $this->class);
    }

    /**
     * Print two columns in the console.
     */
    public function twoColumn(string $first, ?string $second = null): void
    {
        $this->command?->outputComponents()->twoColumnDetail($first, $second);
    }

    /**
     * Print a line in the console.
     */
    public function line(string $line): void
    {
        $this->command?->line($line);
    }

    /**
     * Print a comment in the console.
     */
    public function comment(string $string): void
    {
        $this->command?->comment($string);
    }
}
