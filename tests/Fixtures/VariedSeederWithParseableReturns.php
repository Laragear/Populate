<?php

namespace Tests\Fixtures;

use Laragear\Populate\Seeder;

class VariedSeederWithParseableReturns extends Seeder
{
    public function __construct(public $factory, public $model, public $collection)
    {
        //
    }

    public function seedFactory()
    {
        return $this->factory;
    }

    public function seedModel()
    {
        return $this->model;
    }

    public function seedCollection()
    {
        return $this->collection;
    }
}
