<?php

namespace Laragear\Populate;

use Illuminate\Pipeline\Pipeline;

/**
 * @method $this send(\Laragear\Populate\Seeding $passable)
 * @method \Laragear\Populate\Seeding thenReturn()
 *
 * @internal
 */
class Populator extends Pipeline
{
    /**
     * The default storage path to save incomplete seeding.
     *
     * @const string
     */
    public const STORAGE_PATH = 'framework/seeding';

    /**
     * If the Seeding should use transactions.
     *
     * @var bool|null
     */
    public ?bool $useTransactions = null;

    /**
     * The array of class pipes.
     *
     * @var array
     */
    protected $pipes = [
        Pipes\FindInvokedSeeder::class,
        Pipes\FindSeedSteps::class,
        Pipes\WrapSeedSteps::class,
        Pipes\MayWrapStepsIntoTransactions::class,
        Pipes\MaySkipSeeder::class,
        Pipes\RunSeedSteps::class,
        Pipes\MayCallAfter::class,
        Pipes\RemoveContinueDataAndFile::class,
    ];

    /**
     * Sets the usage of database transactions.
     *
     * @return $this
     */
    public function setUseTransactions(bool $use): static
    {
        $this->useTransactions ??= $use;

        return $this;
    }
}
