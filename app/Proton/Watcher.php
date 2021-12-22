<?php

namespace App\Proton;

//---------------------------------------------------------------------------------
// Proton Watcher
//---------------------------------------------------------------------------------
class Watcher
{
    protected Config $config;
    protected Data $data;
    protected AssetManager $assetManager;
    protected FilesystemManager $fsManager;
    protected PageManager $pageManager;
    // protected \Spatie\Watcher\Watch $watcher;
    protected Watch $watcher;
    protected \App\Commands\Watch $cmd;
    protected ProcessInterface $server;

    public function __construct(\App\Commands\Watch $cmd)
    {
        $this->cmd = $cmd;
        $this->config = new Config();

        $this->fsManager = new FilesystemManager($this->config);
        $this->fsManager->pathChecker();

        $data = new Data($this->config);
        $this->pageManager = new PageManager($this->config, $data);
        $this->assetManager = new AssetManager($this->config);

        // $this->watcher = \Spatie\Watcher\Watch::path($this->config->settings->paths->watch);
        $this->watcher = Watch::path($this->config->settings->paths->watch);

        $this->server = $this->initServer();
    }

    public function watch(): void
    {
        // Init Build
        $this->cmd->info("Compiling Initital Build");
        $this->fsManager->clearCache();
        $this->fsManager->cleanupDist();
        $this->pageManager->compilePages();
        $this->assetManager->copyAssets();

        // Srtart the server
        $this->cmd->info("Starting Server...");
        $this->server->start();

        $this->cmd->info("Watching...");

        $this->watcher->onAnyChange(function (string $type, string $path) {
            // $this->cmd->info("$type: $path");

            if ($this->isDataPath($path)) {
                // Data changed, Need to recompile all pages
                $this->refreshData();
            } elseif ($type === \Spatie\Watcher\Watch::EVENT_TYPE_FILE_DELETED) {
                // File Deleted
                $this->fileDeleteAction($path);
            } elseif ($type === \Spatie\Watcher\Watch::EVENT_TYPE_FILE_CREATED ||
                      $type === \Spatie\Watcher\Watch::EVENT_TYPE_FILE_UPDATED) {
                // File Updated
                $this->fileUpdateAction($path);
            }
        })->start();
    }

    protected function initServer(): ProcessInterface
    {
        if ("browsersync" === $this->config->settings->devserver) {
            return new BrowserSyncServer($this->config->settings->paths->dist);
        }
        return new PHPServer($this->config->settings->paths->dist);
    }

    protected function runNPMBuild(): void
    {
        $command = $this->config->settings->npmBuild;
        if ($command) {
            $this->cmd->info("Running NPM Build: $command");
            $process = new TerminalCommand($command);
            $process->start();
        }
    }

    protected function fileUpdateAction(string $path): void
    {
        if ($this->isAssetsPath($path)) {
            $this->cmd->info("Asset Updated: $path");
            $this->assetManager->copyAssets();
        } elseif ($this->isTemplatesPath($path)) {
            // Recompile Pages
            $this->cmd->info("Template Updated: $path");
            $this->pageManager->compilePages();
        } else {
            // Dirs outside of the proton files
            $this->runNPMBuild();
        }
    }

    protected function fileDeleteAction(string $path): void
    {
        if ($this->isPagesPath($path)) {
            // Delete Page
            $this->cmd->info("Page Deleted: $path");
            $this->deletePage($path);
        } elseif ($this->isAssetsPath($path)) {
            // Delete Asset
            $this->cmd->info("Asset Deleted: $path");
            $this->deleteAsset($path);
        } elseif ($this->isTemplatesPath($path)) {
            // Recompile Pages
            $this->cmd->info("Template Deleted: $path");
            $this->pageManager->compilePages();
        } else {
            // Dirs outside of the proton files
            $this->runNPMBuild();
        }
    }

    protected function deleteAsset(string $path): void
    {
        // strip first two folders since it wont be in dist
        $parts = explode(DIRECTORY_SEPARATOR, $path);
        array_shift($parts);
        array_shift($parts);
        $deleteFile = implode(DIRECTORY_SEPARATOR, $parts);
        $this->fsManager->deleteFromDist($deleteFile);
    }

    protected function deletePage(string $path): void
    {
        // strip first folder since it wont be in dist
        $parts = explode(DIRECTORY_SEPARATOR, $path);
        array_shift($parts);
        $deleteFile = implode(DIRECTORY_SEPARATOR, $parts);
        $this->fsManager->deleteFromDist($deleteFile);
    }

    protected function refreshData(): void
    {
        $this->cmd->info("Refreshing Data");
        $this->pageManager->refreshData();
        $this->cmd->info("Recompiling All Pages");
        $this->pageManager->compilePages();
    }

    protected function isInPath(string $path, string $pathKey): bool
    {
        return false !== strpos($path, $pathKey);
    }

    public function isDataPath(string $path): bool
    {
        return $this->isInPath($path, $this->config->settings->paths->data);
    }

    public function isAssetsPath(string $path): bool
    {
        return $this->isInPath($path, $this->config->settings->paths->assets);
    }

    public function isPagesPath(string $path): bool
    {
        return $this->isInPath($path, $this->config->settings->paths->pages);
    }

    public function isLayoutsPath(string $path): bool
    {
        return $this->isInPath($path, $this->config->settings->paths->layouts);
    }

    public function isPartialsPath(string $path): bool
    {
        return $this->isInPath($path, $this->config->settings->paths->partials);
    }

    public function isMacrosPath(string $path): bool
    {
        return $this->isInPath($path, $this->config->settings->paths->macros);
    }

    public function isTemplatesPath(string $path): bool
    {
        return
            $this->isDataPath($path)     ||
            $this->isLayoutsPath($path)  ||
            $this->isPagesPath($path)    ||
            $this->isMacrosPath($path)   ||
            $this->isPartialsPath($path);
    }
}
