<?php

namespace App\Commands;

use LaravelZero\Framework\Commands\Command;

class Build extends Command
{
    // The signature of the command.
    protected $signature = 'build

                            {--clear-cache|cc : Clear template cache (optional)}';

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
        $this->info('Cleaning previous builds');
        $fsManager->cleanupDist();

        if ($this->option('clear-cache')) {
            $fsManager->clearCache();
        }

        //----------------------------------
        // Load in Data
        //----------------------------------
        $this->info('Loading data');
        $data = new \App\Proton\Data($config);

        if ($config->settings->debug) {
            $this->info('Collected Data:');
            $data->dump();
        }

        //----------------------------------
        // Process all pages
        //----------------------------------
        $this->info('Compiling Pages');
        $pageManger = new \App\Proton\PageManager($config, $data);
        $pageManger->compilePages();

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
        $assetManger = new \App\Proton\AssetManager($config);
        $assetManger->copyAssets();

        $this->info('Build Complete');
    }
}
