<?php

namespace App\Proton;

use Symfony\Component\Process\ExecutableFinder;
use Symfony\Component\Process\Process;

//---------------------------------------------------------------------------------
// Proton BrowserSyncServer
//---------------------------------------------------------------------------------
class BrowserSyncServer implements ProcessInterface
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
            (new ExecutableFinder)->find('node'),
            realpath(__DIR__ . '/../bin/browser-sync.js'),
            $this->path,
        ];

        $this->process = new Process(
            command: $command,
            timeout: null,
        );

        $this->process->start();

        $this->process->waitUntil(function ($type, $buffer) {
            if (Process::ERR === $type) {
                echo 'ERR:'.$buffer;
            } else {
                echo $buffer;
            }
            return false !== strpos($buffer, 'Watching files');
        });

        return $this->process;
    }
}
