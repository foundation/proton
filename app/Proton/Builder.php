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
        $start = microtime(true);

        if ($this->config->settings->debug) {
            $this->output->info('Configuration:');
            $this->config->dump();
            $this->output->info('Collected Data:');
            $this->data->dump();
        }
        $pageCount  = $this->compilePages();
        $this->buildSitemap();
        $assetCount = $this->copyAssets();
        $this->runNPMBuild();

        $elapsed = round((microtime(true) - $start) * 1000);
        $this->printBuildStats($pageCount, $assetCount, $elapsed);
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

    public function copyAssets(): int
    {
        $this->output->info('Copying Assets');

        return $this->assetManager->copyAssets();
    }

    public function compilePages(): int
    {
        $this->output->info('Compiling Pages');

        return $this->pageManager->compilePages();
    }

    private function printBuildStats(int $pages, int $assets, float $elapsed): void
    {
        $distDir  = $this->config->settings->paths->dist;
        $size     = FilesystemManager::getDirectorySize($distDir);
        $sizeStr  = $this->formatBytes($size);

        $this->output->info('Build Summary');
        $this->output->detail("Pages:  $pages");
        $this->output->detail("Assets: $assets");
        $this->output->detail("Output: $sizeStr");
        $this->output->detail("Time:   {$elapsed}ms");
    }

    private function formatBytes(int $bytes): string
    {
        if ($bytes < 1024) {
            return "$bytes B";
        }
        if ($bytes < 1048576) {
            return round($bytes / 1024, 1) . ' KB';
        }

        return round($bytes / 1048576, 1) . ' MB';
    }

    public function refreshData(): void
    {
        $this->output->info('Refreshing Data');
        $this->pageManager->refreshData();
        $this->output->info('Recompiling All Pages');
        $this->pageManager->compilePages();
    }
}
