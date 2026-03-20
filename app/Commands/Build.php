<?php

namespace App\Commands;

use App\Proton\AssetManager;
use App\Proton\Builder;
use App\Proton\Config;
use App\Proton\ConsoleOutput;
use App\Proton\Data;
use App\Proton\FilesystemManager;
use App\Proton\PageManager;
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
     */
    public function handle(): void
    {
        $output       = new ConsoleOutput($this);
        $config       = app(Config::class);
        $data         = app(Data::class);
        $fsManager    = app(FilesystemManager::class);
        $pageManager  = app(PageManager::class);
        $assetManager = app(AssetManager::class);

        $builder = new Builder($output, $config, $data, $fsManager, $pageManager, $assetManager);
        $builder->clean(boolval($this->option('clean')));
        $builder->build();

        $this->info('Build Complete');
    }
}
