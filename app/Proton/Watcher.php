<?php

namespace App\Proton;

// ---------------------------------------------------------------------------------
// Proton Watcher
// ---------------------------------------------------------------------------------
class Watcher
{
    protected bool $running = true;

    /** Debounce wait in microseconds (200ms) */
    protected int $debounceInterval = 200_000;

    /** Poll interval in microseconds (500ms) */
    protected int $pollInterval = 500_000;

    public function __construct(
        protected Output $output,
        protected Config $config,
        protected Builder $builder,
        protected FilesystemManager $fsManager,
        protected FileScanner $scanner,
        protected DevServer $server,
    ) {
        $this->installSignalHandlers();
    }

    public function watch(): void
    {
        // Initial build
        $this->builder->clean(true);
        $this->builder->build();

        // Start the dev server
        $this->output->info('Starting Server...');
        $this->server->start();

        // Take initial snapshot after build
        $this->scanner->snapshot();

        $this->output->info('Watching...');

        while ($this->running) {
            $this->dispatchSignals();

            $changes = $this->scanner->scan();

            if ($changes !== []) {
                // Debounce: wait and re-scan until stable
                $changes = $this->debounce($changes);
                $this->processBatch($changes);
                $this->server->notifyRebuild();
            }

            usleep($this->pollInterval);
        }

        $this->output->info('Stopping server...');
        $this->server->stop();
    }

    /**
     * Debounce: after detecting changes, wait and re-scan until no new changes appear.
     *
     * @param array<int, array{type: string, path: string}> $initial
     *
     * @return array<int, array{type: string, path: string}>
     */
    protected function debounce(array $initial): array
    {
        $all  = [];
        $seen = [];

        // Collect initial changes
        foreach ($initial as $change) {
            $key = $change['type'] . ':' . $change['path'];
            if (!isset($seen[$key])) {
                $seen[$key] = true;
                $all[]      = $change;
            }
        }

        // Keep re-scanning until stable
        while (true) {
            usleep($this->debounceInterval);
            $more = $this->scanner->scan();

            if ($more === []) {
                break;
            }

            foreach ($more as $change) {
                $key = $change['type'] . ':' . $change['path'];
                if (!isset($seen[$key])) {
                    $seen[$key] = true;
                    $all[]      = $change;
                }
            }
        }

        return $all;
    }

    /**
     * Process a batch of changes, executing each rebuild action at most once.
     *
     * @param array<int, array{type: string, path: string}> $changes
     */
    protected function processBatch(array $changes): void
    {
        $needsDataRefresh = false;
        $needsPageCompile = false;
        $needsAssetCopy   = false;
        $needsSitemap     = false;
        $needsNpmBuild    = false;
        $deletedPages     = [];
        $deletedAssets    = [];

        foreach ($changes as $change) {
            $type = $change['type'];
            $path = $change['path'];

            if ($this->isDataPath($path)) {
                $needsDataRefresh = true;
                continue;
            }

            if ($type === FileScanner::EVENT_FILE_DELETED) {
                if ($this->isPagesPath($path)) {
                    $deletedPages[] = $path;
                    $needsSitemap   = true;
                } elseif ($this->isAssetsPath($path)) {
                    $deletedAssets[] = $path;
                } elseif ($this->isTemplatesPath($path)) {
                    $needsPageCompile = true;
                } else {
                    $needsNpmBuild = true;
                }
            } elseif ($type === FileScanner::EVENT_FILE_UPDATED) {
                if ($this->isAssetsPath($path)) {
                    $this->output->info("Asset Updated: $path");
                    $needsAssetCopy = true;
                } elseif ($this->isTemplatesPath($path)) {
                    $this->output->info("Template Updated: $path");
                    $needsPageCompile = true;
                } else {
                    $needsNpmBuild = true;
                }
            } elseif ($type === FileScanner::EVENT_FILE_CREATED) {
                if ($this->isAssetsPath($path)) {
                    $this->output->info("Asset Created: $path");
                    $needsAssetCopy = true;
                } elseif ($this->isTemplatesPath($path)) {
                    $this->output->info("Template Created: $path");
                    $needsPageCompile = true;
                    if ($this->isPagesPath($path)) {
                        $needsSitemap = true;
                    }
                } else {
                    $needsNpmBuild = true;
                }
            }
        }

        // Execute each action at most once
        if ($needsDataRefresh) {
            $this->output->info('Data changed, refreshing...');
            $this->builder->refreshData();
        }

        foreach ($deletedPages as $path) {
            $this->output->info("Page Deleted: $path");
            $this->deletePage($path);
        }

        foreach ($deletedAssets as $path) {
            $this->output->info("Asset Deleted: $path");
            $this->deleteAsset($path);
        }

        if ($needsAssetCopy) {
            $this->builder->copyAssets();
        }

        if ($needsPageCompile) {
            $this->builder->compilePages();
        }

        if ($needsSitemap) {
            $this->builder->buildSitemap();
        }

        if ($needsNpmBuild) {
            $this->builder->runNPMBuild();
        }
    }

    protected function installSignalHandlers(): void
    {
        if (!function_exists('pcntl_signal')) {
            return;
        }

        pcntl_signal(SIGINT, function (): void {
            $this->running = false;
        });
        pcntl_signal(SIGTERM, function (): void {
            $this->running = false;
        });
    }

    protected function dispatchSignals(): void
    {
        if (function_exists('pcntl_signal_dispatch')) {
            pcntl_signal_dispatch();
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
        return str_contains($path, $pathKey);
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
            $this->isDataPath($path)
            || $this->isLayoutsPath($path)
            || $this->isPagesPath($path)
            || $this->isMacrosPath($path)
            || $this->isPartialsPath($path);
    }
}
