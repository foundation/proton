<?php

namespace App\Proton\Settings;

final class Settings
{
    public function __construct(
        public string $defaultExt = 'html',
        public string $domain = 'https://www.example.com',
        public bool $autoindex = true,
        public bool $debug = false,
        public bool $pretty = true,
        public bool $minify = false,
        public bool $sitemap = true,
        public string $npmBuild = 'yarn build',
        public string $devserver = 'php',
        public int $port = 8000,
        public Layouts $layouts = new Layouts(),
        public Paths $paths = new Paths(),
    ) {
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            defaultExt: (string)($data['defaultExt'] ?? 'html'),
            domain: (string)($data['domain'] ?? 'https://www.example.com'),
            autoindex: (bool)($data['autoindex'] ?? true),
            debug: (bool)($data['debug'] ?? false),
            pretty: (bool)($data['pretty'] ?? true),
            minify: (bool)($data['minify'] ?? false),
            sitemap: (bool)($data['sitemap'] ?? true),
            npmBuild: (string)($data['npmBuild'] ?? 'yarn build'),
            devserver: (string)($data['devserver'] ?? 'php'),
            port: (int)($data['port'] ?? 8000),
            layouts: Layouts::fromArray((array)($data['layouts'] ?? [])),
            paths: Paths::fromArray((array)($data['paths'] ?? [])),
        );
    }
}
