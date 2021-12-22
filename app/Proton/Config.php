<?php

namespace App\Proton;

use Symfony\Component\Yaml\Yaml;

//---------------------------------------------------------------------------------
// Proton Configuration
//---------------------------------------------------------------------------------
class Config
{
    const CONFIGFILES = [
        "proton.yml",
        ".proton.yml",
    ];
    const DEFAULTS = [
        "defaultExt" => "html",
        "domain"     => "https://www.example.com",
        "autoindex"  => true,
        "debug"      => false,
        "pretty"     => true,
        "minify"     => false,
        "sitemap"    => true,
        "watch"      => [
            "npmCommand" => "yarn build",
        ],
        "layouts"    => [
            "default" => "default.html",
            "rules" => [
                // "blog" => "blog.html",
            ]
        ],
        "paths" => [
            "dist"     => "dist",
            "assets"   => "src/assets",
            "data"     => "src/data",
            "layouts"  => "src/layouts",
            "macros"   => "src/macros",
            "pages"    => "src/pages",
            "partials" => "src/partials",
            "watch"    => "src",
        ],
    ];

    /** @var mixed $settings */
    public $settings;

    public function __construct()
    {
        $this->settings = self::getSettings();
    }

    public function initConfigFile(): bool
    {
        if (!$this->configFileExists()) {
            $yaml = Yaml::dump($this->settings, 2, 4, Yaml::DUMP_OBJECT_AS_MAP);
            file_put_contents(self::CONFIGFILES[0], $yaml);
            return true;
        }
        return false;
    }

    public function configFileExists(): bool
    {
        foreach (self::CONFIGFILES as $configFile) {
            if (file_exists($configFile)) {
                return true;
            }
        }
        return false;
    }

    public function dump(): void
    {
        print_r($this->settings);
    }

    /**
     * @return mixed
     */
    public static function getSettings()
    {
        // Set default data
        $config = self::DEFAULTS;
        // Config file override
        foreach (self::CONFIGFILES as $configFile) {
            if (file_exists($configFile)) {
                $config = array_merge($config, Yaml::parseFile($configFile));
                break;
            }
        }
        // Make it an object
        return json_decode((string) json_encode($config));
    }
}
