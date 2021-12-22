<?php

namespace App\Proton;

use Symfony\Component\Process\ExecutableFinder;
use Symfony\Component\Process\Process;

//---------------------------------------------------------------------------------
// Proton BrowserSyncServer
//---------------------------------------------------------------------------------
class TerminalCommand implements ProcessInterface
{
    public string $done;
    public Process $process;

    public function __construct(string $process, string $done="")
    {
        $this->done = $done;
        $this->process = Process::fromShellCommandline($process);
    }

    public function stop(): void
    {
        $this->process->stop();
    }

    public function start(): void
    {
        if (empty($this->done)) {
            // If done is not provided run linear
            $this->process->run(function ($type, $buffer) {
                if (Process::ERR === $type) {
                    echo 'ERR > '.$buffer;
                } else {
                    echo $buffer;
                }
            });
        } else {
            // Run Async print output until done string
            $this->process->start();
            $this->process->waitUntil(function ($type, $buffer) {
                echo $buffer;
                return false !== strpos($buffer, $this->done);
            });
        }
    }
}
