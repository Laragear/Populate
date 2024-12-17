<?php

namespace Tests\Pipelines;

use Illuminate\Container\Container;
use Illuminate\Database\Console\Seeds\SeedCommand;
use Illuminate\Filesystem\Filesystem;
use Laragear\Populate\Pipes\RemoveContinueDataAndFile;
use Laragear\Populate\Seeding;
use Tests\Fixtures\EmptySeeder;
use Tests\TestCase;

class RemoveContinueFileTest extends TestCase
{
    public function test_doesnt_removes_file_if_container_not_application(): void
    {
        $this->mock(Filesystem::class)->expects('delete')->never();

        $container = new Container();

        $passable = new Seeding($container, $this->mock(SeedCommand::class), new EmptySeeder(), []);

        $this->app->make(RemoveContinueDataAndFile::class)
            ->handle($passable, function (Seeding $seeding) {
                //
            });
    }

    public function test_removes_file(): void
    {
        $this->mock(Filesystem::class)->expects('delete')
            ->with($this->app->storagePath('/framework/seeding/test.json'))->andReturnTrue();

        $passable = new Seeding($this->app, $this->mock(SeedCommand::class), new EmptySeeder(), [], class: 'test');

        $this->app->make(RemoveContinueDataAndFile::class)
            ->handle($passable, function (Seeding $seeding) {
                //
            });
    }
}
