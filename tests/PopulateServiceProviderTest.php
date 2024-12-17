<?php

namespace Tests;

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Database\Console\Seeds\SeedCommand;
use Illuminate\Support\Facades\Artisan;
use Laragear\MetaTesting\InteractsWithServiceProvider;
use Laragear\Populate\Commands\SeederMakeCommand;
use Laragear\Populate\Populator;

class PopulateServiceProviderTest extends TestCase
{
    use InteractsWithServiceProvider;

    public function test_registers_populator_as_singleton(): void
    {
        $this->assertHasSingletons(Populator::class);
    }

    public function test_adds_option_to_command(): void
    {
        $options = $this->app->make(SeedCommand::class)->getDefinition()->getOptions();

        static::assertArrayHasKey('continue', $options);
        static::assertSame(
            'Resume from a previous failed or incomplete seeding.',
            $options['continue']->getDescription()
        );
    }

    public function test_adds_super_seeder_command(): void
    {
        $commands = $this->app->make(Kernel::class)->all();

        static::assertArrayHasKey('make:super-seeder', $commands);
        static::assertInstanceOf(SeederMakeCommand::class, $commands['make:super-seeder']);
    }
}
