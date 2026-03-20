<?php

namespace App\Commands;

use App\Proton\AssetManager;
use App\Proton\Builder;
use App\Proton\Config;
use App\Proton\ConsoleOutput;
use App\Proton\Data;
use App\Proton\DevServer;
use App\Proton\FileScanner;
use App\Proton\FilesystemManager;
use App\Proton\PageManager;
use App\Proton\Watcher;
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
        $scanner = new FileScanner([$config->settings->paths->watch]);
        $server  = new DevServer($config->settings->paths->dist);

        $watcher = new Watcher($output, $config, $builder, $fsManager, $scanner, $server);
        $watcher->watch();
    }
}
