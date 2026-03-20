<?php

namespace App\Commands;

use App\Proton\Config;
use App\Proton\FilesystemManager;
use App\Proton\TerminalCommand;
use LaravelZero\Framework\Commands\Command;

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
     */
    public function handle(): void
    {
        $config    = app(Config::class);
        $fsManager = app(FilesystemManager::class);

        $templateOption = $this->option('template');
        if (is_string($templateOption)) {
            $clone = Config::SITES_TEMPLATE;
            if ($templateOption !== 'sites') {
                $clone = $templateOption;
            }
            if (preg_match("/^http\S+git$/", $clone)) {
                $this->info("Cloning $clone");
                $command = "git clone $clone .";
                $process = new TerminalCommand($command);
                $process->start();
                FilesystemManager::rm_rf('.git');
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
        $this->info('Initiating Proton Folders');
        $fsManager->initPaths();
        $this->info('Folders Created:');
        $fsManager->printPaths();

        $this->info('Init Complete');
    }
}
