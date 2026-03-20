<?php

namespace App\Proton;

// ---------------------------------------------------------------------------------
// Proton Sitemap
// ---------------------------------------------------------------------------------
class Sitemap
{
    public const SITEMAP = 'sitemap.xml';
    public const EXTS    = ['html', 'php'];

    public function __construct(protected Config $config, protected FilesystemManager $fsManager)
    {
    }

    public function write(): void
    {
        $dir = $this->config->settings->paths->dist;

        $assets = array_filter($this->fsManager->getAllFiles($dir), function (string $file): bool {
            $ext = pathinfo($file, PATHINFO_EXTENSION);

            return in_array($ext, self::EXTS);
        });

        $domain  = $this->config->settings->domain;
        $sitemap = new \samdark\sitemap\Sitemap($dir . DIRECTORY_SEPARATOR . self::SITEMAP);
        foreach ($assets as $asset) {
            $url = $domain . '/' . $asset;
            $sitemap->addItem($url);
        }
        $sitemap->write();
    }
}
