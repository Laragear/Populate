<?php

namespace Laragear\Populate\Pipes;

use Closure;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Laragear\Populate\Attributes\SeedStep;
use Laragear\Populate\ContinueData;
use Laragear\Populate\Exceptions\SkipSeeding;
use Laragear\Populate\Seeder;
use Laragear\Populate\Seeding;
use ReflectionMethod;
use Throwable;
use function method_exists;

/**
 * @internal
 */
class WrapSeedSteps
{
    /**
     * Create a new Wrap Seed Steps instance.
     */
    public function __construct(protected ContinueData $data)
    {
        //
    }

    /**
     * Handle the incoming seeding.
     */
    public function handle(Seeding $seeding, Closure $next): mixed
    {
        $withoutEvents = $this->seederWithoutModelEvents($seeding->seeder);

        $seeding->steps->transform(
            function (ReflectionMethod $method) use ($seeding, $withoutEvents): Closure {
                return function () use ($seeding, $method, $withoutEvents): void {
                    $seedStep = $this->parseMethodAttribute($method, $withoutEvents);

                    if ($this->seedStepAlreadyRan($seeding, $method->name, $seedStep)) {
                        return;
                    }

                    $result = $this->handleSeedStep(
                        $seeding, $method, $seedStep, $seeding->parameters[$method->name] ?? []
                    );

                    if ($result === true) {
                        $seeding->twoColumn("~ $seedStep->as", '<fg=green;options=bold>DONE</>');

                        $this->data->continue[$seeding->seeder::class][$method->name] = true;
                    }
                };
            },
        );

        return $next($seeding);
    }

    /**
     * Check if the method already ran.
     */
    protected function seedStepAlreadyRan(Seeding $seeding, string $method, SeedStep $step): bool
    {
        if (isset($this->data->continue[$seeding->seeder::class][$method])) {
            $seeding->twoColumn("~ $step->as", '<fg=gray;options=bold>CONTINUE</>');

            return true;
        }

        return false;
    }

    /**
     * Parse the results of the Seed Step result.
     */
    protected function parseResult(mixed $result): void
    {
        match (true) {
            $result instanceof Factory => $result->create(),
            $result instanceof Model => $result->push(),
            $result instanceof Collection => $result->each->push(),
            default => null,
        };
    }

    /**
     * Call the Seed Step of the Seeder.
     *
     * @throws \Throwable
     */
    protected function handleSeedStep(
        Seeding $seeding,
        ReflectionMethod $method,
        SeedStep $step,
        array $parameters,
    ): bool {
        try {
            $this->parseResult($this->runSeedStep($seeding, $method, $parameters, $step->withoutModelEvents));
        } catch (SkipSeeding $e) {
            return $this->outputSeedStepSkipped($seeding, $step, $e);
        } catch (UniqueConstraintViolationException $e) {
            if ($seeding->seeder->useTransactions && $step->retryUnique > 0) {
                $seeding->twoColumn("~ $step->as", '<fg=yellow;options=bold>RETRY UNIQUE</>');

                --$step->retryUnique;

                return $this->handleSeedStep($seeding, $method, $step, $seeding->parameters[$method->name] ?? []);
            }

            $this->throwStepError($seeding, $step, $e);
        } catch (Throwable $e) {
            $this->throwStepError($seeding, $step, $e);
        }

        return true;
    }

    /**
     * Check if the entire seeder uses Model Events.
     */
    protected function seederWithoutModelEvents(Seeder $seeder): bool
    {
        return isset(array_flip(class_uses_recursive($seeder))[WithoutModelEvents::class]);
    }

    /**
     * Executes the Seed Step.
     */
    protected function runSeedStep(Seeding $seeding, ReflectionMethod $method, array $parameters, bool $silent): mixed
    {
        if ($silent) {
            if (method_exists($seeding->seeder, 'withoutModelEvents')) {
                return $seeding->seeder->withoutModelEvents(
                    fn(): mixed => $seeding->container->call([$seeding->seeder, $method->name], $parameters),
                )();
            }

            return Model::withoutEvents(
                fn(): mixed => $seeding->container->call([$seeding->seeder, $method->name], $parameters),
            );
        }

        return $seeding->container->call([$seeding->seeder, $method->name], $parameters);
    }

    /**
     * Parse the Method Attribute to retrieve the output name and events configuration.
     */
    protected function parseMethodAttribute(ReflectionMethod $method, bool $withoutModelEvents): SeedStep
    {
        /** @var \Laragear\Populate\Attributes\SeedStep $attribute */
        $attribute = Arr::first($method->getAttributes(SeedStep::class))?->newInstance()
            ?? new SeedStep();

        $attribute->as = $attribute->as ?: Str::ucfirst(Str::snake($method->name, ' '));
        $attribute->withoutModelEvents ??= $withoutModelEvents;

        return $attribute;
    }

    /**
     * Outputs the Seed Step that was skipped to the console.
     */
    protected function outputSeedStepSkipped(Seeding $seeding, SeedStep $seedStep, SkipSeeding $e): false
    {
        $seeding->twoColumn("~ $seedStep->as", '<fg=blue;options=bold>SKIPPED</>');

        if ($e->getMessage()) {
            $seeding->comment("  {$e->getMessage()}");
        }

        return false;
    }

    /**
     * Throws an exception and output in the console when the Seed Step errors.
     */
    protected function throwStepError(Seeding $seeding, SeedStep $step, Throwable $e): never
    {
        $seeding->twoColumn("! $step->as", '<fg=red;options=bold>ERROR</>');

        throw $e;
    }
}
