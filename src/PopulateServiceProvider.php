<?php

namespace Laragear\Populate;

use Illuminate\Database\Console\Seeds\SeedCommand;
use Illuminate\Support\ServiceProvider;

/**
 * @internal
 */
class PopulateServiceProvider extends ServiceProvider
{
    /**
     * The path to the stub file.
     *
     * @const string
     */
    public const STUB = __DIR__.'/../stubs/super-seeder.stub';

    /**
     * Registers the application services.
     */
    public function register(): void
    {
        $this->app->singleton(Populator::class);
        $this->app->singleton(ContinueData::class);

        $this->app->resolving(SeedCommand::class, static function (SeedCommand $command): void {
            $command->addOption('continue', description: 'Resume from a previous failed or incomplete seeding.');
        });

        $this->commands(Commands\SuperSeederMakeCommand::class);

        $this->publishes([static::STUB => $this->app->basePath('stubs/super-seeder.stub')], 'stubs');
    }
}
