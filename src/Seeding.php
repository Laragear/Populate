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
     * @param  class-string  $class
     * @param  \Illuminate\Support\Collection<int, \Closure>  $steps
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
}
