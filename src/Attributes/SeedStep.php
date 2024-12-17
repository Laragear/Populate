<?php

namespace Laragear\Populate\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_METHOD)]
class SeedStep
{
    /**
     * Create a new Seed Step instance.
     */
    public function __construct(public string $as = '', public ?bool $withoutModelEvents = null)
    {
        //
    }
}
