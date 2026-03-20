<?php

namespace App\Proton;

class NullOutput implements Output
{
    public function info(string $message): void
    {
    }
}
