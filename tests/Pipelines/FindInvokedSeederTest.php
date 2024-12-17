<?php

namespace Tests\Pipelines;

use Illuminate\Console\Command;
use Illuminate\Database\ConnectionResolverInterface;
use Illuminate\Database\Console\Seeds\SeedCommand;
use Laragear\Populate\Pipes\FindInvokedSeeder;
use Laragear\Populate\Seeder;
use Laragear\Populate\Seeding;
use Orchestra\Testbench\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;

class FindInvokedSeederTest extends TestCase
{
    public function test_doesnt_finds_seeder_if_no_command_instance(): void
    {
        $passable = new Seeding($this->app, null, $this->mock(Seeder::class), []);

        $this->app->make(FindInvokedSeeder::class)
            ->handle($passable, function (Seeding $passable) {
                static::assertSame('DatabaseSeeder', $passable->class);
            });
    }

    public static function provideSeeder(): array
    {
        return [
            ['argument', 'option'],
            ['option', 'argument'],
        ];
    }

    #[DataProvider('provideSeeder')]
    public function test_finds_seeder_by_default_name(string $first, string $second): void
    {
        $command = $this->mock(SeedCommand::class);

        if ($first === 'argument') {
            $command->expects($first)->with('class')->andReturn('DatabaseSeeder');
            $command->expects($second)->never();
        } else {
            $command->expects($second)->with('class')->andReturnNull();
            $command->expects($first)->with('class')->andReturn('DatabaseSeeder');
        }

        $passable = new Seeding($this->app, $command, $this->mock(Seeder::class), []);

        $this->app->make(FindInvokedSeeder::class)
            ->handle($passable, function (Seeding $passable) {
                static::assertSame('DatabaseSeeder', $passable->class);
            });
    }

    #[DataProvider('provideSeeder')]
    public function test_finds_seeder_by_custom_name(string $first, string $second): void
    {
        $command = $this->mock(SeedCommand::class);

        if ($first === 'argument') {
            $command->expects($first)->with('class')->andReturn('CustomSeeder');
            $command->expects($second)->never();
        } else {
            $command->expects($second)->with('class')->andReturnNull();
            $command->expects($first)->with('class')->andReturn('CustomSeeder');
        }

        $passable = new Seeding($this->app, $command, $this->mock(Seeder::class), []);

        $this->app->make(FindInvokedSeeder::class)
            ->handle($passable, function (Seeding $passable) {
                static::assertSame('Database\Seeders\CustomSeeder', $passable->class);
            });
    }
}
