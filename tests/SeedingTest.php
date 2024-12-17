<?php

namespace Tests;

use Illuminate\Console\Command;
use Laragear\Populate\Seeder;
use Laragear\Populate\Seeding;

class SeedingTest extends TestCase
{
    public function test_class_name_for_file_name(): void
    {
        $seeding = new Seeding(
            $this->app, $this->mock(Command::class), new class extends Seeder {}, [], class: 'Foo\BarBaz\Quz'
        );

        static::assertSame('Foo_BarBaz_Quz', $seeding->classFileName());
    }
}
