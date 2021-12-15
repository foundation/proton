<?php

namespace App\Commands;

use LaravelZero\Framework\Commands\Command;

class Init extends Command
{
    /**
     * The signature of the command.
     *
     * @var string
     */
    protected $signature = 'init

                            {--config: Init a config file with default values (optional)}';

    /**
     * The description of the command.
     *
     * @var string
     */
    protected $description = 'Create the folder needed to build your site';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $config = new \App\Proton\Config();

        // Create config file
        if ($this->option('config')) {
            $this->info('Initiating Proton config');
            if (!$config->initConfigFile()) {
                $this->info('Config already exists');
            }
            $this->info('Configuration:');
            $config->dump();
        }

        // Setup Folders
        $fsManager = new \App\Proton\FilesystemManager($config);
        $this->info('Initiating Proton Folders');
        $fsManager->initPaths();
        $this->info('Folders Created:');
        $fsManager->printPaths();

        $this->info('Init Complete');
    }
}
