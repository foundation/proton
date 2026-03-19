<?php

namespace App\Proton;

//---------------------------------------------------------------------------------
// Proton FileScanner — Pure PHP file watcher using mtime polling
//---------------------------------------------------------------------------------
class FileScanner
{
    const EVENT_FILE_CREATED = 'fileCreated';
    const EVENT_FILE_UPDATED = 'fileUpdated';
    const EVENT_FILE_DELETED = 'fileDeleted';

    /** @var array<string, int> path => mtime */
    protected array $mtimes = [];

    /** @var string[] */
    protected array $paths;

    public function __construct(array $paths)
    {
        $this->paths = $paths;
    }

    /**
     * Build the initial mtime snapshot.
     */
    public function snapshot(): void
    {
        $this->mtimes = $this->buildMtimeMap();
    }

    /**
     * Scan for changes since the last snapshot/scan.
     *
     * @return array<int, array{type: string, path: string}>
     */
    public function scan(): array
    {
        $current = $this->buildMtimeMap();
        $changes = [];

        // Check for new or updated files
        foreach ($current as $path => $mtime) {
            if (!isset($this->mtimes[$path])) {
                $changes[] = ['type' => self::EVENT_FILE_CREATED, 'path' => $path];
            } elseif ($mtime !== $this->mtimes[$path]) {
                $changes[] = ['type' => self::EVENT_FILE_UPDATED, 'path' => $path];
            }
        }

        // Check for deleted files
        foreach ($this->mtimes as $path => $mtime) {
            if (!isset($current[$path])) {
                $changes[] = ['type' => self::EVENT_FILE_DELETED, 'path' => $path];
            }
        }

        $this->mtimes = $current;

        return $changes;
    }

    /**
     * Build a map of file path => mtime for all watched paths.
     *
     * @return array<string, int>
     */
    protected function buildMtimeMap(): array
    {
        $map = [];

        foreach ($this->paths as $watchPath) {
            if (!is_dir($watchPath)) {
                continue;
            }

            $directory = new \RecursiveDirectoryIterator($watchPath);
            $directory->setFlags(\RecursiveDirectoryIterator::SKIP_DOTS);
            $iterator = new \RecursiveIteratorIterator($directory);

            foreach ($iterator as $info) {
                // Skip dot files (matching existing convention)
                if (str_starts_with($info->getFilename(), '.')) {
                    continue;
                }

                if ($info->isFile()) {
                    $map[$info->getPathname()] = $info->getMTime();
                }
            }
        }

        return $map;
    }
}
