<?php

namespace App\Proton;

use Symfony\Component\Process\ExecutableFinder;
use Symfony\Component\Process\Process;

//---------------------------------------------------------------------------------
// Proton DevServer — PHP built-in server with live reload
//---------------------------------------------------------------------------------
class DevServer implements ProcessInterface
{
    public string $path;
    public Process $process;
    protected string $reloadFile;

    public function __construct(string $path)
    {
        $this->path = $path;
        $this->reloadFile = sys_get_temp_dir() . '/proton_reload_' . getmypid();

        $command = [
            (new ExecutableFinder)->find('php'),
            '-S', 'localhost:8000',
            '-t', $this->path,
            realpath(__DIR__ . '/../bin/router.php'),
        ];

        $this->process = new Process(
            command: $command,
            timeout: null,
            env: ['PROTON_RELOAD_FILE' => $this->reloadFile],
        );
    }

    public function start(): void
    {
        // Write initial timestamp so the reload file exists
        file_put_contents($this->reloadFile, (string) time());

        $this->process->start();

        if (! $this->process->isRunning()) {
            throw new \Exception("Could not start PHP dev server. Error output: " . $this->process->getErrorOutput());
        }

        $this->process->waitUntil(function ($type, $buffer) {
            echo $buffer;
            return false !== strpos($buffer, 'started');
        });
    }

    public function stop(): void
    {
        $this->process->stop();

        // Clean up temp file
        if (file_exists($this->reloadFile)) {
            @unlink($this->reloadFile);
        }
    }

    /**
     * Notify the browser that a rebuild has completed.
     */
    public function notifyRebuild(): void
    {
        file_put_contents($this->reloadFile, (string) time());
    }
}
