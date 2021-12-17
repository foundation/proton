<?php

namespace App\Proton;

//---------------------------------------------------------------------------------
// Proton Sitemap
//---------------------------------------------------------------------------------
class Sitemap
{
    const SITEMAP = 'sitemap.xml';

    protected Config $config;

    public function __construct(Config $config)
    {
        $this->config = $config;
    }

    public function write(): void
    {
        $dir = $this->config->settings->paths->dist;
        $fsManager = new FilesystemManager($this->config);
        $assets = $fsManager->getAllFiles($dir);
        $domain = $this->config->settings->domain;
        $sitemap = new \samdark\sitemap\Sitemap($dir .DIRECTORY_SEPARATOR. self::SITEMAP);
        foreach ($assets as $asset) {
            $url = $domain .'/'. $asset;
            $sitemap->addItem($url);
        }
        $sitemap->write();
    }
}
