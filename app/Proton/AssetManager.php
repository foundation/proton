<?php

namespace App\Proton;

use App\Proton\Settings\Paths;

class AssetManager
{
    protected Paths $paths;

    public function __construct(protected Config $config)
    {
        $this->paths = $this->config->settings->paths;
    }

    public function copyAssets(): void
    {
        $fsManager = new FilesystemManager($this->config);
        $assets    = $fsManager->getAllFiles($this->paths->assets);
        foreach ($assets as $asset) {
            $from = $this->paths->assets . DIRECTORY_SEPARATOR . $asset;
            $to   = $this->paths->dist . DIRECTORY_SEPARATOR . $asset;
            $dir  = dirname($to);
            if (!file_exists($dir)) {
                mkdir($dir, 0777, true);
            }
            copy($from, $to);
        }
    }
}
