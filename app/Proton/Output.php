<?php

namespace App\Proton;

interface Output
{
    public function info(string $message): void;
}
