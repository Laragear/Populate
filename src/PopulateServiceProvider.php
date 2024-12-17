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
     * Registers the application services.
     */
    public function register(): void
    {
        $this->app->singleton(Populator::class);
        $this->app->singleton(ContinueData::class);

        $this->app->resolving(SeedCommand::class, static function (SeedCommand $command): void {
            $command->addOption('continue', description: 'Resume from a previous failed or incomplete seeding.');
        });

        $this->commands(Commands\SeederMakeCommand::class);
    }
}
