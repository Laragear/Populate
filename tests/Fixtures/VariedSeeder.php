<?php

namespace Tests\Fixtures;

use Laragear\Populate\Attributes\SeedStep;
use Laragear\Populate\Seeder;

class VariedSeeder extends Seeder
{
    public array $ran = [];

    public function seed()
    {
        $this->ran[] = __METHOD__;
    }

    public function seedSecond()
    {
        $this->ran[] = __METHOD__;
    }

    public static function seedPublicStaticInvalid()
    {

    }

    #[SeedStep]
    public function withAttribute()
    {
        $this->ran[] = __METHOD__;
    }

    #[SeedStep]
    public static function publicStaticWithAttributeInvalid()
    {

    }

    protected function seedInvalid()
    {

    }

    #[SeedStep]
    protected function seedWithAttributeInvalid()
    {

    }

    protected static function seedProtectedStaticInvalid()
    {

    }

    #[SeedStep]
    protected static function protectedStaticWithAttributeInvalid()
    {

    }
}
