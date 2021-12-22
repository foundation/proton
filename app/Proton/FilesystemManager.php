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

    public function getAllFiles(string $path): array
    {
        $directory = new \RecursiveDirectoryIterator($path);
        $directory->setFlags(\RecursiveDirectoryIterator::SKIP_DOTS);
        $iterator = new \RecursiveIteratorIterator($directory);
        $files = [];
        // The length of the pages folder name + /
        $dirLength = strlen($path)+1;
        foreach ($iterator as $info) {
            // Remove the pages fodler name from the file name
            $files[] = substr_replace($info->getPathname(), '', 0, $dirLength);
        }
        return $files;
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

    public function deleteFromDist(string $path): bool
    {
        $filepath = $this->paths->dist .DIRECTORY_SEPARATOR. $path;
        if (file_exists($filepath)) {
            return unlink($filepath);
        }
        return true;
    }

    public function pathChecker(): bool
    {
        if ($this->pathsExist()) {
            return true;
        }
        throw new \Exception('Not all required paths exist to build site. You can run `proton init` to ensure everything is setup.');
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

    public function clearCache(): void
    {
        self::rm_rf(PageManager::CACHEDIR);
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
