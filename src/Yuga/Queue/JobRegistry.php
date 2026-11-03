<?php

declare(strict_types=1);

namespace Yuga\Queue;

use InvalidArgumentException;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use ReflectionClass;
use RuntimeException;
use Yuga\Interfaces\Queue\JobInterface;
use Yuga\Queue\Attributes\QueueJob;

final class JobRegistry
{
    /** @var array<class-string, true> */
    private array $jobs = [];

    /**
     * Explicit registration, including jobs provided by packages.
     *
     * @param class-string $class
     */
    public function register(string $class): self
    {
        $reflection = $this->reflect($class);

        if (
            !$reflection->isInstantiable()
            || !$reflection->implementsInterface(JobInterface::class)
            || !$reflection->hasMethod('run')
            || !$reflection->getMethod('run')->isPublic()
            || $reflection->getMethod('run')->isStatic()
        ) {
            throw new InvalidArgumentException(
                "Job [{$class}] must be concrete, implement JobInterface, "
                    . 'and have a public instance run() method.'
            );
        }

        $this->jobs[$reflection->getName()] = true;

        return $this;
    }

    /**
     * Discover attributed jobs using PSR-4 directory mappings.
     *
     * @param array<string, string> $roots Directory => namespace
     */
    public function discover(array $roots): self
    {
        foreach ($roots as $directory => $namespace) {
            $root = realpath($directory);

            if ($root === false || !is_dir($root)) {
                throw new InvalidArgumentException(
                    "Job discovery directory does not exist: {$directory}"
                );
            }

            $namespace = trim($namespace, '\\');

            if ($namespace === '') {
                throw new InvalidArgumentException(
                    'Job discovery requires a namespace.'
                );
            }

            $files = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator(
                    $root,
                    RecursiveDirectoryIterator::SKIP_DOTS
                )
            );

            foreach ($files as $file) {
                if (
                    !$file->isFile()
                    || $file->isLink()
                    || $file->getExtension() !== 'php'
                ) {
                    continue;
                }

                $relative = substr(
                    $file->getPathname(),
                    strlen($root) + 1,
                    -4
                );

                $class = $namespace . '\\' . str_replace(
                    ['/', '\\'],
                    '\\',
                    $relative
                );

                // Composer loads the expected PSR-4 class.
                if (!class_exists($class)) {
                    throw new RuntimeException(
                        "Cannot autoload discovered job candidate [{$class}]."
                    );
                }

                $reflection = $this->reflect($class);

                if (
                    realpath((string) $reflection->getFileName())
                    !== $file->getRealPath()
                ) {
                    throw new RuntimeException(
                        "Discovery file does not match class [{$class}]."
                    );
                }

                if ($reflection->getAttributes(QueueJob::class) !== []) {
                    $this->register($class);
                }
            }
        }

        return $this;
    }

    /** @return list<class-string> */
    public function classes(): array
    {
        return array_keys($this->jobs);
    }

    private function reflect(string $class): ReflectionClass
    {
        if (!class_exists($class)) {
            throw new InvalidArgumentException(
                "Cannot autoload job [{$class}]."
            );
        }

        return new ReflectionClass($class);
    }
}
