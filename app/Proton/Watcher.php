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
    }

    public function watch(): void
    {
        // Init Build
        $this->fsManager->clearCache();
        $this->fsManager->cleanupDist();
        $this->pageManager->compilePages();
        $this->assetManager->copyAssets();

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

    protected function fileUpdateAction(string $path): void
    {
        $this->cmd->info("File Updated: $path");
        if ($this->isAssetsPath($path)) {
            $this->assetManager->copyAssets();
        } elseif ($this->isTemplatesPath($path)) {
            // Recompile Pages
            $this->pageManager->compilePages();
        } else {
            // Dirs outside of the proton files
        }
    }

    protected function fileDeleteAction(string $path): void
    {
        $this->cmd->info("File Deleted: $path");
        if ($this->isPagesPath($path)) {
            // Delete Page
            $this->deletePage($path);
        } elseif ($this->isAssetsPath($path)) {
            // Delete Asset
            $this->deleteAsset($path);
        } elseif ($this->isTemplatesPath($path)) {
            // Recompile Pages
            $this->pageManager->compilePages();
        } else {
            // Dirs outside of the proton files
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
        $this->pageManager->refreshData();
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
