<?php

namespace App\Proton;

use Symfony\Component\Process\ExecutableFinder;
use Symfony\Component\Process\Process;

// ---------------------------------------------------------------------------------
// Proton DevServer — PHP built-in server with live reload
// ---------------------------------------------------------------------------------
class DevServer implements ProcessInterface
{
    public Process $process;
    protected string $reloadFile;

    public function __construct(public string $path)
    {
        $this->reloadFile = sys_get_temp_dir() . '/proton_reload_' . getmypid();

        $command = [
            new ExecutableFinder()->find('php'),
            '-S', 'localhost:8000',
            '-t', $this->path,
            realpath(__DIR__ . '/../bin/router.php'),
        ];

        $this->process = new Process(
            command: $command,
            env: ['PROTON_RELOAD_FILE' => $this->reloadFile],
            timeout: null,
        );
    }

    public function start(): void
    {
        // Write initial timestamp so the reload file exists
        file_put_contents($this->reloadFile, (string)time());

        $this->process->start();

        if (!$this->process->isRunning()) {
            throw new Exceptions\BuildException('Could not start PHP dev server. Error output: ' . $this->process->getErrorOutput());
        }

        $this->process->waitUntil(function ($type, $buffer): bool {
            echo $buffer;

            return str_contains($buffer, 'started');
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
        file_put_contents($this->reloadFile, (string)time());
    }
}
