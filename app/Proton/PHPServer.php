<?php

namespace App\Proton;

use Symfony\Component\Process\ExecutableFinder;
use Symfony\Component\Process\Process;

//---------------------------------------------------------------------------------
// Proton BrowserSyncServer
//---------------------------------------------------------------------------------
class PHPServer implements ProcessInterface
{
    public string $path;
    public Process $process;

    public function __construct(string $path)
    {
        $this->path = $path;

        $command = [
            (new ExecutableFinder)->find('php'),
            "-S", "localhost:8000", "-t",
            $this->path,
        ];
        $this->process = new Process(
            command: $command,
            timeout: null,
        );
    }

    public function stop(): void
    {
        $this->process->stop();
    }

    public function start(): void
    {
        $this->process->start();

        if (! $this->process->isRunning()) {
            throw new \Exception("Could not start PHP server. Error output: " . $this->process->getErrorOutput());
        }

        $this->process->waitUntil(function ($type, $buffer) {
            echo $buffer;
            return false !== strpos($buffer, 'started');
        });
    }
}
