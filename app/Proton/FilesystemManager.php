<?php

namespace App\Proton;

//---------------------------------------------------------------------------------
// Proton FilesystemManager
//---------------------------------------------------------------------------------
class FilesystemManager
{
    /** @var mixed $paths */
    public $paths;

    public function __construct(Config $config)
    {
        $this->paths = $config->settings->paths;
    }

    public function printPaths(): void
    {
        // Create all folders from paths config
        foreach ($this->paths as $name => $path) {
            echo "\t[$name] => $path".PHP_EOL;
        }
    }

    public function pathsExist(): bool
    {
        // Check if all paths exist
        foreach ($this->paths as $name => $path) {
            if ("dist" === $name) {
                // Dist does not need to exist for proton to function
                continue;
            }
            if (!file_exists($path)) {
                return false;
            }
        }
        return true;
    }

    public function initPaths(): void
    {
        // Create all folders from paths config
        foreach ($this->paths as $name => $path) {
            if (!file_exists($path)) {
                mkdir($path, 0777, true);
            }
        }
    }

    public function cleanupDist(): void
    {
        self::rm_rf($this->paths->dist);
    }

    public static function rm_rf(string $dir): void
    {
        if (file_exists($dir)) {
            $directory = new \RecursiveDirectoryIterator($dir);
            $directory->setFlags(\RecursiveDirectoryIterator::SKIP_DOTS);
            $iterator = new \RecursiveIteratorIterator($directory);
            foreach ($iterator as $info) {
                unlink($info->getPathname());
                // This leaves empty dirs... should fix eventually
            }
        }
    }
}
