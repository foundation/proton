<?php

namespace App\Proton;

use Symfony\Component\Process\ExecutableFinder;
use Symfony\Component\Process\Process;

//---------------------------------------------------------------------------------
// Proton BrowserSyncServer
//---------------------------------------------------------------------------------
class PHPServer implements ServerInterface
{
    public string $path;
    public Process $process;

    public function __construct(string $path)
    {
        $this->path = $path;
    }

    public function stop(): void
    {
        $this->process->stop();
    }

    public function start(): Process
    {
        $command = [
            (new ExecutableFinder)->find('php'),
            "-S", "localhost:8000", "-t",
            $this->path,
        ];

        $this->process = new Process(
            command: $command,
            timeout: null,
        );

        $this->process->start();

        $this->process->waitUntil(function ($type, $buffer) {
            echo $buffer;
            return false !== strpos($buffer, 'started');
        });

        return $this->process;
    }
}
