<?php

namespace App\Proton;

//---------------------------------------------------------------------------------
// Proton Sitemap
//---------------------------------------------------------------------------------
class Sitemap
{
    const SITEMAP = 'sitemap.xml';
    const EXTS = ["html", "php"];

    protected Config $config;

    public function __construct(Config $config)
    {
        $this->config = $config;
    }

    public function write(): void
    {
        $dir = $this->config->settings->paths->dist;
        $fsManager = new FilesystemManager($this->config);

        $assets = array_filter($fsManager->getAllFiles($dir), function ($file) {
            $ext = pathinfo($file, PATHINFO_EXTENSION);
            return in_array($ext, self::EXTS);
        });

        $domain = $this->config->settings->domain;
        $sitemap = new \samdark\sitemap\Sitemap($dir .DIRECTORY_SEPARATOR. self::SITEMAP);
        foreach ($assets as $asset) {
            $url = $domain .'/'. $asset;
            $sitemap->addItem($url);
        }
        $sitemap->write();
    }
}
