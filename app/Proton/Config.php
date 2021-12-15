<?php

namespace App\Proton;

use Symfony\Component\Yaml\Yaml;

//---------------------------------------------------------------------------------
// Proton Configuration
//---------------------------------------------------------------------------------
class Config
{
    const CONFIGFILES = [
        ".proton",
        "proton.yml",
    ];
    const DEFAULTS = [
        "defaultExt" => "html",
        "autoindex"  => true,
        "debug"      => false,
        "pretty"     => true,
        "minify"     => false,
        "layouts"    => [
            "default" => "default.html",
            "rules" => [
                // "blog" => "blog.html",
            ]
        ],
        "paths" => [
            "dist"     => "dist",
            "data"     => "src/data",
            "layouts"  => "src/layouts",
            "macros"   => "src/macros",
            "pages"    => "src/pages",
            "partials" => "src/partials",
        ],
    ];

    /**
     * @return mixed
     */
    public static function getConfig()
    {
        // Set default data
        $config = self::DEFAULTS;
        // Config file override
        foreach (self::CONFIGFILES as $configFile) {
            if (file_exists($configFile)) {
                $config = array_merge($config, Yaml::parseFile($configFile));
            }
        }
        // Make it an object
        return json_decode((string) json_encode($config));
    }
}
