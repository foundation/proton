<?php

namespace App\Proton;

use LaravelZero\Framework\Commands\Command;

class ConsoleOutput implements Output
{
    public function __construct(protected Command $command)
    {
    }

    public function info(string $message): void
    {
        $this->command->info($message);
    }
}
