<?php

namespace Laragear\Populate;

/**
 * @internal
 */
class ContinueData
{
    /**
     * Create a new Continue Data instance.
     *
     * @param  array<class-string, string[]>  $continue
     */
    public function __construct(public array $continue = [])
    {
        //
    }
}
