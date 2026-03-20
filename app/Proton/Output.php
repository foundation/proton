<?php

namespace App\Proton;

interface Output
{
    public function info(string $message): void;

    public function detail(string $message): void;
}
