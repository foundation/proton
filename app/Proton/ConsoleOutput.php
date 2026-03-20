<?php

namespace App\Proton;

use LaravelZero\Framework\Commands\Command;

class ConsoleOutput implements Output
{
    public function __construct(protected Command $command, protected bool $verbose = false, protected bool $quiet = false)
    {
    }

    public function info(string $message): void
    {
        if (!$this->quiet) {
            $this->command->info($message);
        }
    }

    public function detail(string $message): void
    {
        if ($this->verbose) {
            $this->command->line("  $message");
        }
    }
}
