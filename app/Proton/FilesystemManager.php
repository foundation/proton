<?php

namespace App\Proton;

use App\Proton\Exceptions\FilesystemException;
use App\Proton\Settings\Paths;

class FilesystemManager
{
    public Paths $paths;

    public function __construct(Config $config)
    {
        $this->paths = $config->settings->paths;
    }

    public function printPaths(): void
    {
        foreach ($this->getPathMap() as $name => $path) {
            echo "\t[$name] => $path" . PHP_EOL;
        }
    }

    /**
     * @return array<int, string>
     */
    public function getAllFiles(string $path): array
    {
        $directory = new \RecursiveDirectoryIterator($path);
        $directory->setFlags(\RecursiveDirectoryIterator::SKIP_DOTS);
        $iterator = new \RecursiveIteratorIterator($directory);
        $files    = [];
        // The length of the pages folder name + /
        $dirLength = strlen($path) + 1;
        foreach ($iterator as $info) {
            // Skip dot files
            if (!str_starts_with((string)$info->getFilename(), '.')) {
                // Remove the pages folder name from the file name
                $files[] = substr_replace($info->getPathname(), '', 0, $dirLength);
            }
        }

        return $files;
    }

    public function pathsExist(): bool
    {
        foreach ($this->getPathMap() as $name => $path) {
            if ('dist' === $name) {
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
        $filepath = $this->paths->dist . DIRECTORY_SEPARATOR . $path;
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
        throw new FilesystemException('Not all required paths exist to build site. You can run `proton init` to ensure everything is setup.');
    }

    public function initPaths(): void
    {
        foreach ($this->getPathMap() as $path) {
            if (!file_exists($path) && !mkdir($path, 0777, true)) {
                throw new FilesystemException("Failed to create directory: $path");
            }
        }
    }

    public function cleanupDist(): void
    {
        self::removeDirectory($this->paths->dist);
    }

    public function clearCache(): void
    {
        self::removeDirectory(PageManager::CACHEDIR);
    }

    public static function removeDirectory(string $dir): void
    {
        if (file_exists($dir)) {
            $directory = new \RecursiveDirectoryIterator($dir);
            $directory->setFlags(\RecursiveDirectoryIterator::SKIP_DOTS);
            $iterator = new \RecursiveIteratorIterator($directory, \RecursiveIteratorIterator::CHILD_FIRST);

            foreach ($iterator as $info) {
                if ($info->isDir()) {
                    rmdir($info->getPathname());
                } else {
                    unlink($info->getPathname());
                }
            }
            rmdir($dir);
        }
    }

    /**
     * @return array<string, string>
     */
    private function getPathMap(): array
    {
        return [
            'dist'     => $this->paths->dist,
            'assets'   => $this->paths->assets,
            'data'     => $this->paths->data,
            'layouts'  => $this->paths->layouts,
            'macros'   => $this->paths->macros,
            'pages'    => $this->paths->pages,
            'partials' => $this->paths->partials,
            'watch'    => $this->paths->watch,
        ];
    }
}
