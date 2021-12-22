<?php

namespace App\Commands;

use LaravelZero\Framework\Commands\Command;

class Watch extends Command
{
    /**
     * The signature of the command.
     *
     * @var string
     */
    protected $signature = 'watch';

    /**
     * The description of the command.
     *
     * @var string
     */
    protected $description = 'Watch the template folders for changes and rebuild';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        //----------------------------------
        // Watch
        //----------------------------------
        $watcher = new \App\Proton\Watcher($this);
        $watcher->watch();
    }
}
