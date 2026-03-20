<?php

namespace App\Proton;

use Symfony\Component\Process\Process;

// ---------------------------------------------------------------------------------
// Proton BrowserSyncServer
// ---------------------------------------------------------------------------------
class TerminalCommand implements ProcessInterface
{
    public Process $process;

    public function __construct(string $process, public string $done= '')
    {
        $this->process = Process::fromShellCommandline($process);
    }

    public function stop(): void
    {
        $this->process->stop();
    }

    public function start(): void
    {
        if ($this->done === '' || $this->done === '0') {
            // If done is not provided run linear
            $this->process->run(function ($type, string $buffer): void {
                if (Process::ERR === $type) {
                    echo 'ERR > ' . $buffer;
                } else {
                    echo $buffer;
                }
            });
        } else {
            // Run Async print output until done string
            $this->process->start();
            $this->process->waitUntil(function ($type, $buffer): bool {
                echo $buffer;

                return str_contains($buffer, $this->done);
            });
        }
    }
}
