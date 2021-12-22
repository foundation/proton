<?php

namespace App\Commands;

use LaravelZero\Framework\Commands\Command;

class Build extends Command
{
    // The signature of the command.
    protected $signature = 'build

                            {--clean : Clean previous build (optional)}';

    // The description of the command.
    protected $description = 'Build all pages';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        //----------------------------------
        // Build
        //----------------------------------
        $builder = new \App\Proton\Builder($this);
        $builder->clean(boolval($this->option('clean')));
        $builder->build();

        $this->info('Build Complete');
    }
}
