<?php

namespace App\Proton;

class NullOutput implements Output
{
    public function info(string $message): void
    {
    }

    public function detail(string $message): void
    {
    }
}
