<?php

namespace App\Proton;

//---------------------------------------------------------------------------------
// Proton PageManager
//---------------------------------------------------------------------------------
class AssetManager
{
    protected Config $config;

    /** @var mixed $paths */
    protected $paths;

    public function __construct(Config $config)
    {
        $this->config = $config;
        $this->paths  = $config->settings->paths;
    }

    public function copyAssets(): void
    {
        $fsManager = new FilesystemManager($this->config);
        $assets = $fsManager->getAllFiles($this->paths->assets);
        foreach ($assets as $asset) {
            $from = $this->paths->assets .DIRECTORY_SEPARATOR. $asset;
            $to   = $this->paths->dist .DIRECTORY_SEPARATOR. $asset;
            $dir  = dirname($to);
            if (!file_exists($dir)) {
                mkdir($dir, 0777, true);
            }
            copy($from, $to);
        }
    }
}
