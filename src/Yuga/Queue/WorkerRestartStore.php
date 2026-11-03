<?php

declare(strict_types=1);

namespace Yuga\Queue;

use RuntimeException;

final class WorkerRestartStore
{
    public function __construct(private readonly string $path) {}

    public function token(): string
    {
        return $this->access(false);
    }

    public function request(): void
    {
        $this->access(true);
    }

    private function access(bool $write): string
    {
        $directory = dirname($this->path);

        if (
            !is_dir($directory)
            && !mkdir($directory, 0775, true)
            && !is_dir($directory)
        ) {
            throw new RuntimeException(
                'Could not create the worker restart directory.'
            );
        }

        $handle = fopen($this->path, 'c+b');

        if ($handle === false) {
            throw new RuntimeException(
                'Could not open the worker restart file.'
            );
        }

        try {
            if (!flock($handle, $write ? LOCK_EX : LOCK_SH)) {
                throw new RuntimeException(
                    'Could not lock the worker restart file.'
                );
            }

            if (!$write) {
                rewind($handle);
                $token = stream_get_contents($handle);

                if ($token === false) {
                    throw new RuntimeException('Could not read restart token.');
                }

                return $token;
            }

            $token = bin2hex(random_bytes(32));

            if (!ftruncate($handle, 0) || !rewind($handle)) {
                throw new RuntimeException('Could not reset restart token.');
            }

            $offset = 0;

            while ($offset < strlen($token)) {
                $written = fwrite($handle, substr($token, $offset));

                if ($written === false || $written === 0) {
                    throw new RuntimeException('Could not write restart token.');
                }

                $offset += $written;
            }

            if (!fflush($handle)) {
                throw new RuntimeException('Could not flush restart token.');
            }

            return $token;
        } finally {
            flock($handle, LOCK_UN);
            fclose($handle);
        }
    }
}
