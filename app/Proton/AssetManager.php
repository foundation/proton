<?php

namespace App\Proton;

use App\Proton\Settings\Paths;

class AssetManager
{
    protected Paths $paths;

    public function __construct(protected Config $config, protected FilesystemManager $fsManager)
    {
        $this->paths = $this->config->settings->paths;
    }

    public function copyAssets(): void
    {
        $assets = $this->fsManager->getAllFiles($this->paths->assets);
        foreach ($assets as $asset) {
            $from = $this->paths->assets . DIRECTORY_SEPARATOR . $asset;
            $to   = $this->paths->dist . DIRECTORY_SEPARATOR . $asset;
            $dir  = dirname($to);
            if (!file_exists($dir) && !mkdir($dir, 0777, true)) {
                throw new Exceptions\FilesystemException("Failed to create directory: $dir");
            }
            if (!copy($from, $to)) {
                throw new Exceptions\FilesystemException("Failed to copy asset: $from -> $to");
            }
        }
    }
}
