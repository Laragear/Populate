<?php

namespace Laragear\Populate\Pipes;

use Closure;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Model;
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
                    [$output, $silent] = $this->parseMethodAttribute($method, $withoutEvents);

                    if ($this->seedStepAlreadyRan($seeding, $method->name, $output)) {
                        return;
                    }

                    $this->parseResult(
                        $this->handleSeedStep(
                            $seeding, $method, $output, $seeding->parameters[$method->name] ?? [], $silent
                        ),
                    );

                    $this->data->continue[$seeding->seeder::class][$method->name] = true;
                };
            },
        );

        return $next($seeding);
    }

    /**
     * Check if the method already ran.
     */
    protected function seedStepAlreadyRan(Seeding $seeding, string $method, string $output): bool
    {
        if (isset($this->data->continue[$seeding->seeder::class][$method])) {
            $seeding->command?->outputComponents()->twoColumnDetail("↳ $output", '<fg=gray;options=bold>CONTINUE</>');

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
        string $output,
        array $parameters,
        bool $withoutEvents,
    ): mixed {
        {
            try {
                $result = $this->runSeedStep($seeding, $method, $parameters, $withoutEvents);
            } catch (SkipSeeding $e) {
                return $this->outputSeedStepSkipped($seeding, $output, $e);
            } catch (Throwable $e) {
                $seeding->command?->outputComponents()->twoColumnDetail("⚠ $output", '<fg=red;options=bold>ERROR</>');

                throw $e;
            }

            $seeding->command?->outputComponents()->twoColumnDetail(
                "↳ $output", '<fg=green;options=bold>DONE</>',
            );

            return $result;
        }
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
                    fn(): mixed => $seeding->container->call([$seeding->seeder, $method->name], $parameters)
                )();
            }

            return Model::withoutEvents(
                fn(): mixed => $seeding->container->call([$seeding->seeder, $method->name], $parameters)
            );
        }

        return $seeding->container->call([$seeding->seeder, $method->name], $parameters);
    }

    /**
     * Parse the Method Attribute to retrieve the output name and events configuration.
     *
     * @return array{string, bool}
     */
    protected function parseMethodAttribute(ReflectionMethod $method, bool $withoutModelEvents): array
    {
        /** @var \Laragear\Populate\Attributes\SeedStep|null $attribute */
        if ($attribute = Arr::first($method->getAttributes(SeedStep::class))?->newInstance()) {
            return [
                $attribute->as ?: Str::ucfirst(Str::snake($method->name, ' ')),
                $attribute->withoutModelEvents ?? $withoutModelEvents
            ];
        }

        return [Str::ucfirst(Str::snake($method->name, ' ')), $withoutModelEvents];
    }

    /**
     * Outputs the Seed Step that was skipped to the console.
     */
    protected function outputSeedStepSkipped(Seeding $seeding, string $output, SkipSeeding $e): false
    {
        $seeding->command?->outputComponents()->twoColumnDetail(
            "↳ $output", '<fg=blue;options=bold>SKIPPED</>',
        );

        if ($e->getMessage()) {
            $seeding->command?->comment("  {$e->getMessage()}");
        }

        return false;
    }

}
