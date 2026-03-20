<?php

namespace App\Proton\Settings;

final class Paths
{
    public function __construct(
        public string $dist = 'dist',
        public string $assets = 'src/assets',
        public string $data = 'src/data',
        public string $layouts = 'src/layouts',
        public string $macros = 'src/macros',
        public string $pages = 'src/pages',
        public string $partials = 'src/partials',
        public string $watch = 'src',
    ) {
    }

    /**
     * @param array<string, string> $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            dist: $data['dist'] ?? 'dist',
            assets: $data['assets'] ?? 'src/assets',
            data: $data['data'] ?? 'src/data',
            layouts: $data['layouts'] ?? 'src/layouts',
            macros: $data['macros'] ?? 'src/macros',
            pages: $data['pages'] ?? 'src/pages',
            partials: $data['partials'] ?? 'src/partials',
            watch: $data['watch'] ?? 'src',
        );
    }
}
