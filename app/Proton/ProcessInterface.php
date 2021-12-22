<?php

namespace App\Proton;

use Symfony\Component\Process\ExecutableFinder;
use Symfony\Component\Process\Process;

//---------------------------------------------------------------------------------
// Proton ServerInterface
//---------------------------------------------------------------------------------
interface ProcessInterface
{
    public function start();
    public function stop();
}
