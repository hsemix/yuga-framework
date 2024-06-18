<?php

namespace Yuga\Database\Console\Backup;

use Symfony\Component\Process\Process;


class Console
{
    public function run($command)
    {
        // $process = new Process($command);

        $process = Process::fromShellCommandline($command);

        $process->setTimeout(999999999);

        $process->run(function ($type, $buffer) {
            if (Process::ERR === $type) {
                echo 'ERR > '.$buffer;
            } else {
                echo 'OUT > '.$buffer;
            }
        });

        if ($process->isSuccessful()) {
            return true;
        }

        return $process->getErrorOutput();
    }
}
