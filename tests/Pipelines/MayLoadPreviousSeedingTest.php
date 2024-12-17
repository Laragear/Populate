<?php

namespace Tests\Pipelines;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Laragear\Populate\ContinueData;
use Laragear\Populate\Pipes\MayLoadPreviousSeeding;
use Laragear\Populate\Seeding;
use Mockery\MockInterface;
use Tests\Fixtures\EmptySeeder;
use Tests\TestCase;
use TypeError;

class MayLoadPreviousSeedingTest extends TestCase
{
    public function test_doesnt_load_data_if_there_is_no_command(): void
    {
        $this->mock(Filesystem::class, function (MockInterface $mock) {
            $mock->expects('exists')->never();
            $mock->expects('isFile')->never();
        });

        $passable = new Seeding($this->app, null, new EmptySeeder(), []);

        $this->app->make(MayLoadPreviousSeeding::class)
            ->handle($passable, function () {
                static::assertEmpty($this->app->make(ContinueData::class)->continue);
            });
    }

    public function test_doesnt_load_data_if_command_has_no_option(): void
    {
        $this->mock(Filesystem::class, function (MockInterface $mock) {
            $mock->expects('exists')->never();
            $mock->expects('isFile')->never();
        });

        $command = $this->mock(Command::class, function (MockInterface $mock) {
            $mock->expects('option')->with('continue')->andReturnFalse();
        });

        $passable = new Seeding($this->app, $command, new EmptySeeder(), []);

        $this->app->make(MayLoadPreviousSeeding::class)
            ->handle($passable, function () {
                static::assertEmpty($this->app->make(ContinueData::class)->continue);
            });
    }

    public function test_loads_data_if_command_has_option(): void
    {
        $this->mock(Filesystem::class, function (MockInterface $mock) {
            $mock->expects('exists')->with($this->app->storagePath('/framework/seeding/test.json'))->andReturnTrue();
            $mock->expects('isFile')->with($this->app->storagePath('/framework/seeding/test.json'))->andReturnTrue();
            $mock->expects('json')->with($this->app->storagePath('/framework/seeding/test.json'))->andReturn([
                'foo' => ['bar' => true]
            ]);
        });

        $command = $this->mock(Command::class, function (MockInterface $mock) {
            $mock->expects('option')->with('continue')->andReturnTrue();
            $mock->expects('line')->with('Continuing from previous incomplete seeding.');
        });

        $passable = new Seeding($this->app, $command, new EmptySeeder(), [], class: 'test');

        $this->app->make(MayLoadPreviousSeeding::class)
            ->handle($passable, function (Seeding $seeding) {
                static::assertSame(['foo' => ['bar' => true]], $this->app->make(ContinueData::class)->continue);
            });
    }

    public function test_throws_if_container_not_application(): void
    {
        $this->mock(Filesystem::class, function (MockInterface $mock) {
            $mock->expects('exists')->never();
            $mock->expects('isFile')->never();
        });

        $container = new \Illuminate\Container\Container();

        $command = $this->mock(Command::class, function (MockInterface $mock) {
            $mock->expects('option')->with('continue')->andReturnTrue();
        });

        $passable = new Seeding($container, $command, new EmptySeeder(), [], class: 'test');

        $pipe = $this->app->make(MayLoadPreviousSeeding::class);

        $this->expectException(TypeError::class);
        $this->expectExceptionMessage('The container must be an instance of Illuminate\Contracts\Foundation\Application.');

        $pipe->handle($passable, function (Seeding $seeding) {
           //
        });
    }

    public function test_empty_continue_if_file_doesnt_exist(): void
    {
        $this->mock(Filesystem::class, function (MockInterface $mock) {
            $mock->expects('exists')->with($this->app->storagePath('/framework/seeding/test.json'))->andReturnFalse();
            $mock->expects('isFile')->never();
            $mock->expects('json')->never();
        });

        $command = $this->mock(Command::class, function (MockInterface $mock) {
            $mock->expects('option')->with('continue')->andReturnTrue();
        });

        $passable = new Seeding($this->app, $command, new EmptySeeder(), [], class: 'test');

        $this->app->make(MayLoadPreviousSeeding::class)
            ->handle($passable, function () {
                static::assertEmpty($this->app->make(ContinueData::class)->continue);
            });
    }

    public function test_empty_continue_if_file_is_not_file(): void
    {
        $this->mock(Filesystem::class, function (MockInterface $mock) {
            $mock->expects('exists')->with($this->app->storagePath('/framework/seeding/test.json'))->andReturnTrue();
            $mock->expects('isFile')->with($this->app->storagePath('/framework/seeding/test.json'))->andReturnFalse();
            $mock->expects('json')->never();
        });

        $command = $this->mock(Command::class, function (MockInterface $mock) {
            $mock->expects('option')->with('continue')->andReturnTrue();
        });

        $passable = new Seeding($this->app, $command, new EmptySeeder(), [], class: 'test');

        $this->app->make(MayLoadPreviousSeeding::class)
            ->handle($passable, function () {
                static::assertEmpty($this->app->make(ContinueData::class)->continue);
            });
    }
}
