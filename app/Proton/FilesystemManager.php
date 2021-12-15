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

    public function initPaths(): void
    {
        // Create all folders from paths config
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
