<?php

namespace Laragear\Populate\Commands;

use Illuminate\Database\Console\Seeds\SeederMakeCommand as BaseSeederMakeCommand;
use Symfony\Component\Console\Attribute\AsCommand;

/**
 * @internal
 */
#[AsCommand(name: 'make:super-seeder')]
class SeederMakeCommand extends BaseSeederMakeCommand
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'make:super-seeder';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new Super Seeder class with seed steps.';

    /**
     * The type of class being generated.
     *
     * @var string
     */
    protected $type = 'Seeder';

    /**
     * Get the stub file for the generator.
     *
     * @return string
     */
    protected function getStub(): string
    {
        return $this->resolveStubPath('/stubs/super-seeder.stub');
    }

    /**
     * Resolve the fully-qualified path to the stub.
     *
     * @param  string  $stub
     * @return string
     */
    protected function resolveStubPath($stub): string
    {
        return is_file($customPath = $this->laravel->basePath(trim($stub, '/')))
            ? $customPath
            : __DIR__.'/../../stubs'.$stub;
    }
}
