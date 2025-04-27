<?php

namespace Tests;

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Database\Console\Seeds\SeedCommand;
use Laragear\MetaTesting\InteractsWithServiceProvider;
use Laragear\Populate\Commands\SuperSeederMakeCommand;
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
        static::assertInstanceOf(SuperSeederMakeCommand::class, $commands['make:super-seeder']);
    }

    public function test_publishes_super_seeder_stub(): void
    {
        $this->assertPublishes($this->app->basePath('stubs/super-seeder.stub'), 'stubs');
    }
}
