<?php

namespace App\Proton;

use Symfony\Component\Process\ExecutableFinder;
use Symfony\Component\Process\Process;

//---------------------------------------------------------------------------------
// Proton ServerInterface
//---------------------------------------------------------------------------------
interface ServerInterface
{
    public function start();
    public function stop();
}
