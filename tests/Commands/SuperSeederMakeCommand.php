<?php

namespace Tests\Commands;

use Tests\TestCase;
use const PHP_EOL;

class SuperSeederMakeCommand extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->app->make('files')->delete([
            $this->app->basePath(trim('/stubs/super-super-seeder.stub', '/')),
            $this->app->databasePath('/seeders/ExampleSeeder.php')
        ]);
    }

    public function test_makes_seeder(): void
    {
        $this->artisan('make:super-seeder', ['name' => 'ExampleSeeder']);

        static::assertFileExists($this->app->databasePath('/seeders/ExampleSeeder.php'));

        static::assertSame(<<<'SEEDER'
<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Laragear\Populate\Seeder;

class ExampleSeeder extends Seeder
{
    /**
     * Run logic before executing the seed steps.
     */
    public function before(): void
    {
        // if (false) {
        //     $this->skip();
        // }
    }

    /**
     * Populate the database with records.
     */
    public function seed(): void
    {
        //
    }

    /**
     * Run logic after executing the seed steps.
     */
    public function after(): void
    {
        //
    }
}

SEEDER
            , $this->app->make('files')->get($this->app->databasePath('/seeders/ExampleSeeder.php')));
    }

    public function test_uses_stub(): void
    {
        $this->app->make('files')->ensureDirectoryExists(
            $this->app->basePath(trim('/stubs', '/'))
        );
        $this->app->make('files')->put(
            $this->app->basePath(trim('/stubs/super-seeder.stub', '/')), 'test {{ class }} stub' . PHP_EOL
        );

        $this->artisan('make:super-seeder', ['name' => 'ExampleSeeder']);

        static::assertFileExists($this->app->databasePath('/seeders/ExampleSeeder.php'));

        static::assertSame(<<<'SEEDER'
test ExampleSeeder stub

SEEDER
            , $this->app->make('files')->get($this->app->databasePath('/seeders/ExampleSeeder.php')));
    }
}
