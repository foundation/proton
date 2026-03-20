<?php

namespace App\Proton;

// ---------------------------------------------------------------------------------
// Proton Builder
// ---------------------------------------------------------------------------------
class Builder
{
    public function __construct(
        protected Output $output,
        protected Config $config,
        protected Data $data,
        protected FilesystemManager $fsManager,
        protected PageManager $pageManager,
        protected AssetManager $assetManager,
    ) {
    }

    public function build(): void
    {
        if ($this->config->settings->debug) {
            $this->output->info('Configuration:');
            $this->config->dump();
            $this->output->info('Collected Data:');
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
        if ($command !== '' && $command !== '0') {
            $this->output->info("Running NPM Build: $command");
            $process = new TerminalCommand($command);
            $process->start();
        }
    }

    public function buildSitemap(): void
    {
        if ($this->config->settings->sitemap) {
            $this->output->info('Building Sitemap');
            $sitemap = new Sitemap($this->config, $this->fsManager);
            $sitemap->write();
        }
    }

    public function clean(bool $clean = false): void
    {
        if ($clean) {
            $this->output->info('Cleaning previous builds');
            $this->fsManager->cleanupDist();
        }
        $this->fsManager->clearCache();
    }

    public function copyAssets(): void
    {
        $this->output->info('Copying Assets');
        $this->assetManager->copyAssets();
    }

    public function compilePages(): void
    {
        $this->output->info('Compiling Pages');
        $this->pageManager->compilePages();
    }

    public function refreshData(): void
    {
        $this->output->info('Refreshing Data');
        $this->pageManager->refreshData();
        $this->output->info('Recompiling All Pages');
        $this->pageManager->compilePages();
    }
}
