<?php

declare(strict_types=1);

namespace Yuga\Queue\Console;

use InvalidArgumentException;
use Nette\PhpGenerator\PhpFile;
use RuntimeException;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Output\OutputInterface;
use Throwable;
use Yuga\Console\Command;
use Yuga\Interfaces\Queue\JobInterface;
use Yuga\Interfaces\Queue\ShouldQueueInterface;
use Yuga\Queue\Attributes\QueueJob;
use Yuga\Queue\DispatchableTrait;

class MakeQueueJobCommand extends Command
{
    protected $name = 'make:job';

    protected $description = 'Create a queueable PHP job class.';

    protected $path = 'app/Jobs';

    public function handle()
    {
        try {
            $name = str_replace(
                '\\',
                '/',
                trim((string) $this->argument('name'))
            );

            $parts = explode('/', $name);

            foreach ($parts as $part) {
                if (!preg_match('/^[A-Za-z_][A-Za-z0-9_]*$/', $part)) {
                    throw new InvalidArgumentException(
                        'Use a class name or nested name such as Reports/GenerateReport.'
                    );
                }
            }

            $className = array_pop($parts);

            $namespace = trim(
                (string) env('APP_NAMESPACE', 'App'),
                '\\'
            ) . '\\Jobs';

            if ($parts !== []) {
                $namespace .= '\\' . implode('\\', $parts);
            }

            // Generate first: invalid PHP identifiers must not create files.
            $contents = $this->generateJob($namespace, $className);

            $target = path($this->path . '/' . $name . '.php');
            $directory = dirname($target);

            if (
                !is_dir($directory)
                && !mkdir($directory, 0775, true)
                && !is_dir($directory)
            ) {
                throw new RuntimeException(
                    'Could not create the jobs directory.'
                );
            }

            // Exclusive creation prevents overwriting an existing job.
            $handle = @fopen($target, 'xb');

            if ($handle === false) {
                throw new RuntimeException(
                    'Could not create the job file. It may already exist.'
                );
            }

            try {
                $offset = 0;

                while ($offset < strlen($contents)) {
                    $written = fwrite(
                        $handle,
                        substr($contents, $offset)
                    );

                    if ($written === false || $written === 0) {
                        throw new RuntimeException(
                            'Could not write the job file.'
                        );
                    }

                    $offset += $written;
                }

                if (!fflush($handle)) {
                    throw new RuntimeException(
                        'Could not flush the job file.'
                    );
                }
            } catch (Throwable $exception) {
                fclose($handle);
                @unlink($target);

                throw $exception;
            }

            fclose($handle);

            $this->output->writeln(
                "Created: {$target}",
                OutputInterface::OUTPUT_RAW
            );

            $this->output->writeln(
                'The job will be discovered on the next application boot '
                    . 'when its directory is configured for discovery.'
            );

            return self::SUCCESS;
        } catch (Throwable $exception) {
            $this->output->writeln(
                $exception->getMessage(),
                OutputInterface::OUTPUT_RAW
            );

            return self::FAILURE;
        }
    }

    protected function generateJob(
        string $namespaceName,
        string $className
    ): string {
        $file = new PhpFile();
        $file->setStrictTypes();

        $namespace = $file->addNamespace($namespaceName);

        $namespace->addUse(JobInterface::class);
        $namespace->addUse(ShouldQueueInterface::class);
        $namespace->addUse(DispatchableTrait::class);
        $namespace->addUse(QueueJob::class);

        $class = $namespace->addClass($className);

        $class->addAttribute(QueueJob::class);
        $class->addImplement(JobInterface::class);
        $class->addImplement(ShouldQueueInterface::class);
        $class->addTrait(DispatchableTrait::class);

        $class->addMethod('__construct')
            ->setPublic()
            ->setBody('// Store serializable job data here.');

        $class->addMethod('run')
            ->setPublic()
            ->setReturnType('void')
            ->setBody('// Execute the job.');

        return (string) $file;
    }

    protected function getArguments()
    {
        return [
            [
                'name',
                InputArgument::REQUIRED,
                'Job name, for example Reports/GenerateReport',
            ],
        ];
    }
}
