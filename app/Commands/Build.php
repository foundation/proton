<?php

namespace App\Commands;

use Illuminate\Console\Scheduling\Schedule;
use LaravelZero\Framework\Commands\Command;
use Symfony\Component\Yaml\Yaml;

class Build extends Command
{
    // The signature of the command.
    protected $signature = 'build';

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
        // Config Load
        //----------------------------------
        $config = new \App\Proton\Config();
        if ($config->settings->debug) {
            $this->info('Configuration:');
            $config->dump();
        }

        //----------------------------------
        // Clear out dist files
        //----------------------------------
        $fsManager = new \App\Proton\FilesystemManager($config);
        if (!$fsManager->pathsExist()) {
            $this->error('Not all required paths exist to build site. You can run `proton init` to ensure everything is setup.');
            return;
        }
        $this->info('Cleaning previous builds.');
        $fsManager->cleanupDist();

        //----------------------------------
        // Load in Data
        //----------------------------------
        $this->info('Loading data.');
        $data = new \App\Proton\Data($config);

        if ($config->settings->debug) {
            $this->info('Collected Data:');
            $data->dump();
        }

        //----------------------------------
        // Process all pages
        //----------------------------------
        $this->info('Compiling Pages.');
        $pageManger = new \App\Proton\PageManager($config, $data);
        $pageManger->compilePages();


        $this->info('Build Complete.');
    }
}
