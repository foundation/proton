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
        $fsManager->pathChecker();
        $fsManager->clearCache();
        if ($this->option('clean')) {
            $this->info('Cleaning previous builds');
            $fsManager->cleanupDist();
        }

        //----------------------------------
        // Load in Data
        //----------------------------------
        $this->info('Loading Data');
        $data = new \App\Proton\Data($config);

        if ($config->settings->debug) {
            $this->info('Collected Data:');
            $data->dump();
        }

        //----------------------------------
        // Process all pages
        //----------------------------------
        $this->info('Compiling Pages');
        $pageManager = new \App\Proton\PageManager($config, $data);
        $pageManager->compilePages();

        //----------------------------------
        // Create Sitemap
        //----------------------------------
        if ($config->settings->sitemap) {
            $this->info('Building Sitemap');
            $sitemap = new \App\Proton\Sitemap($config);
            $sitemap->write();
        }

        //----------------------------------
        // Copy Assets
        //----------------------------------
        $this->info('Copying Assets');
        $assetManager = new \App\Proton\AssetManager($config);
        $assetManager->copyAssets();

        //----------------------------------
        // NPM Build
        //----------------------------------
        $command = $config->settings->npmBuild;
        if ($command) {
            $this->info("Running NPM Build: $command");
            $process = new \App\Proton\TerminalCommand($command);
            $process->start();
        }

        $this->info('Build Complete');
    }
}
