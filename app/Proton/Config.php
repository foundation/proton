<?php

namespace App\Proton;

use Symfony\Component\Yaml\Yaml;

// ---------------------------------------------------------------------------------
// Proton Configuration
// ---------------------------------------------------------------------------------
class Config
{
    public const SITES_TEMPLATE = 'https://github.com/foundation/proton-sites-template.git';
    public const CONFIGFILES    = [
        'proton.yml',
        '.proton.yml',
    ];
    public const DEFAULTS = [
        'defaultExt' => 'html',
        'domain'     => 'https://www.example.com',
        'autoindex'  => true,
        'debug'      => false,
        'pretty'     => true,
        'minify'     => false,
        'sitemap'    => true,
        'npmBuild'   => 'yarn build',
        'devserver'  => 'php',
        'layouts'    => [
            'default' => 'default.html',
            'rules'   => [
                // "blog" => "blog.html",
            ],
        ],
        'paths' => [
            'dist'     => 'dist',
            'assets'   => 'src/assets',
            'data'     => 'src/data',
            'layouts'  => 'src/layouts',
            'macros'   => 'src/macros',
            'pages'    => 'src/pages',
            'partials' => 'src/partials',
            'watch'    => 'src',
        ],
    ];

    /** @var mixed */
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
        return array_any(self::CONFIGFILES, fn ($configFile): bool => file_exists($configFile));
    }

    public function dump(): void
    {
        print_r($this->settings);
    }

    public static function getSettings(): mixed
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
        return json_decode((string)json_encode($config));
    }
}
