<?php

namespace App\Proton;

use LaravelZero\Framework\Commands\Command;

//---------------------------------------------------------------------------------
// Proton Builder
//---------------------------------------------------------------------------------
class Builder
{
    protected Config $config;
    protected Data $data;
    protected AssetManager $assetManager;
    protected FilesystemManager $fsManager;
    protected PageManager $pageManager;
    protected Command $cmd;

    public function __construct(Command $cmd)
    {
        $this->cmd = $cmd;
        $this->config = new Config();

        $this->fsManager = new FilesystemManager($this->config);
        $this->fsManager->pathChecker();

        $data = new Data($this->config);
        $this->pageManager = new PageManager($this->config, $data);
        $this->assetManager = new AssetManager($this->config);
    }

    public function build(): void
    {
        if ($this->config->settings->debug) {
            $this->cmd->info('Configuration:');
            $this->config->dump();
            $this->cmd->info('Collected Data:');
            $this->data->dump();
        }
        $this->compilePages();
        $this->buildSitemap();
        $this->copyAssets();
        $this->runNPMBuild();
    }

    public function runNPMBuild(): void
    {
        $command = $this->config->settings->npmBuild;
        if ($command) {
            $this->cmd->info("Running NPM Build: $command");
            $process = new TerminalCommand($command);
            $process->start();
        }
    }

    public function buildSitemap(): void
    {
        if ($this->config->settings->sitemap) {
            $this->cmd->info('Building Sitemap');
            $sitemap = new \App\Proton\Sitemap($this->config);
            $sitemap->write();
        }
    }

    public function clean(bool $clean=false): void
    {
        if ($clean) {
            $this->cmd->info('Cleaning previous builds');
            $this->fsManager->cleanupDist();
        }
        $this->fsManager->clearCache();
    }

    public function copyAssets(): void
    {
        $this->cmd->info('Copying Assets');
        $this->assetManager->copyAssets();
    }

    public function compilePages(): void
    {
        $this->cmd->info('Compiling Pages');
        $this->pageManager->compilePages();
    }

    public function refreshData(): void
    {
        $this->cmd->info("Refreshing Data");
        $this->pageManager->refreshData();
        $this->cmd->info("Recompiling All Pages");
        $this->pageManager->compilePages();
    }
}
