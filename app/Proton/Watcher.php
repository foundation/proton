<?php

namespace App\Proton;

use LaravelZero\Framework\Commands\Command;

//---------------------------------------------------------------------------------
// Proton Watcher
//---------------------------------------------------------------------------------
class Watcher
{
    protected Config $config;
    protected Builder $builder;
    protected FilesystemManager $fsManager;
    // protected \Spatie\Watcher\Watch $watcher;
    protected Watch $watcher;
    protected Command $cmd;
    protected ProcessInterface $server;

    public function __construct(Command $cmd)
    {
        $this->cmd = $cmd;
        $this->config = new Config();
        $this->builder = new Builder($cmd);
        $this->server = $this->initServer();
        $this->fsManager = new FilesystemManager($this->config);

        // $this->watcher = \Spatie\Watcher\Watch::path($this->config->settings->paths->watch);
        $this->watcher = Watch::path($this->config->settings->paths->watch);
    }

    public function watch(): void
    {
        // Init Build
        $this->builder->clean(true);
        $this->builder->build();

        // Srtart the server
        $this->cmd->info("Starting Server...");
        $this->server->start();

        $this->cmd->info("Watching...");

        $this->watcher->onAnyChange(function (string $type, string $path) {
            // $this->cmd->info("$type: $path");

            if ($this->isDataPath($path)) {
                // Data changed, Need to recompile all pages
                $this->builder->refreshData();
            } elseif ($type === \Spatie\Watcher\Watch::EVENT_TYPE_FILE_DELETED) {
                // File Deleted
                $this->fileDeleteAction($path);
            } elseif ($type === \Spatie\Watcher\Watch::EVENT_TYPE_FILE_UPDATED) {
                // File Updated
                $this->fileUpdateAction($path);
            } elseif ($type === \Spatie\Watcher\Watch::EVENT_TYPE_FILE_CREATED) {
                // File Created
                $this->fileUpdateAction($path);
                if ($this->isPagesPath($path)) {
                    // Update sitemap for new pages
                    $this->builder->buildSitemap();
                }
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

    protected function fileUpdateAction(string $path): void
    {
        if ($this->isAssetsPath($path)) {
            $this->cmd->info("Asset Updated: $path");
            $this->builder->copyAssets();
        } elseif ($this->isTemplatesPath($path)) {
            // Recompile Pages
            $this->cmd->info("Template Updated: $path");
            $this->builder->compilePages();
        } else {
            // Dirs outside of the proton files
            $this->builder->runNPMBuild();
        }
    }

    protected function fileDeleteAction(string $path): void
    {
        if ($this->isPagesPath($path)) {
            // Delete Page
            $this->cmd->info("Page Deleted: $path");
            $this->deletePage($path);
            $this->builder->buildSitemap();
        } elseif ($this->isAssetsPath($path)) {
            // Delete Asset
            $this->cmd->info("Asset Deleted: $path");
            $this->deleteAsset($path);
        } elseif ($this->isTemplatesPath($path)) {
            // Recompile Pages
            $this->cmd->info("Template Deleted: $path");
            $this->builder->compilePages();
        } else {
            // Dirs outside of the proton files
            $this->builder->runNPMBuild();
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
