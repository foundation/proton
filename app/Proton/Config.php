<?php

namespace App\Proton;

use App\Proton\Exceptions\ConfigException;
use App\Proton\Settings\Settings;
use Symfony\Component\Yaml\Yaml;

class Config
{
    public const SITES_TEMPLATE = 'https://github.com/foundation/proton-sites-template.git';
    public const CONFIGFILES    = [
        'proton.yml',
        '.proton.yml',
    ];

    public Settings $settings;

    public function __construct()
    {
        $this->settings = self::getSettings();
    }

    public function initConfigFile(): bool
    {
        if (!$this->configFileExists()) {
            $yaml   = Yaml::dump($this->toArray($this->settings), 2, 4, Yaml::DUMP_OBJECT_AS_MAP);
            $result = file_put_contents(self::CONFIGFILES[0], $yaml);
            if ($result === false) {
                throw new ConfigException('Failed to write config file: ' . self::CONFIGFILES[0]);
            }

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

    public static function getSettings(): Settings
    {
        $config = [];

        foreach (self::CONFIGFILES as $configFile) {
            if (file_exists($configFile)) {
                try {
                    $parsed = Yaml::parseFile($configFile);
                } catch (\Symfony\Component\Yaml\Exception\ParseException $e) {
                    throw new ConfigException("Failed to parse config file '$configFile': " . $e->getMessage(), 0, $e);
                }
                if (is_array($parsed)) {
                    $config = $parsed;
                }
                break;
            }
        }

        return Settings::fromArray($config);
    }

    /**
     * @return array<string, mixed>
     */
    private function toArray(Settings $settings): array
    {
        return [
            'defaultExt' => $settings->defaultExt,
            'domain'     => $settings->domain,
            'autoindex'  => $settings->autoindex,
            'debug'      => $settings->debug,
            'pretty'     => $settings->pretty,
            'minify'     => $settings->minify,
            'sitemap'    => $settings->sitemap,
            'npmBuild'   => $settings->npmBuild,
            'devserver'  => $settings->devserver,
            'layouts'    => [
                'default' => $settings->layouts->default,
                'rules'   => $settings->layouts->rules,
            ],
            'paths' => [
                'dist'     => $settings->paths->dist,
                'assets'   => $settings->paths->assets,
                'data'     => $settings->paths->data,
                'layouts'  => $settings->paths->layouts,
                'macros'   => $settings->paths->macros,
                'pages'    => $settings->paths->pages,
                'partials' => $settings->paths->partials,
                'watch'    => $settings->paths->watch,
            ],
        ];
    }
}
