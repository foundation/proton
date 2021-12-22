<?php

namespace App\Proton;

//---------------------------------------------------------------------------------
// Proton ServerInterface
//---------------------------------------------------------------------------------
interface ProcessInterface
{
    public function start(): void;
    public function stop(): void;
}
