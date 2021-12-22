<?php

namespace App\Commands;

use LaravelZero\Framework\Commands\Command;
use App\Proton\FilesystemManager;
use App\Proton\TerminalCommand;

class Init extends Command
{
    /**
     * The signature of the command.
     *
     * @var string
     */
    protected $signature = 'init

                            {--config : Init a config file with default values (optional)}
                            {--template= : Clone a Proton template via `sites` or Github *.git URL (optional)}';

    /**
     * The description of the command.
     *
     * @var string
     */
    protected $description = 'Create the folders needed to build with proton';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $config = new \App\Proton\Config();

        if ($this->option('template')) {
            $clone = \App\Proton\Config::SITES_TEMPLATE;
            if ($this->option('template') !== "sites") {
                $clone = strval($this->option('template'));
            }
            if (preg_match("/^http\S+git$/", $clone)) {
                $this->info("Cloning $clone");
                $command = "git clone $clone .";
                $process = new TerminalCommand($command);
                $process->start();
                FilesystemManager::rm_rf(".git");
            }
        }

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
        $fsManager = new FilesystemManager($config);
        $this->info('Initiating Proton Folders');
        $fsManager->initPaths();
        $this->info('Folders Created:');
        $fsManager->printPaths();

        $this->info('Init Complete');
    }
}
