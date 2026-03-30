<?php

namespace App\Proton;

use Symfony\Component\Yaml\Yaml;

final class FrontMatterParser
{
    private const PATTERN_WITH_CONTENT = '/\A---\s*\n(.*?)\n---\s*\n(.*)\z/s';
    private const PATTERN_DATA_ONLY    = '/\A---\s*\n(.*?)\n---\s*\n/s';

    /**
     * Parse front matter and return both the YAML data and remaining content.
     *
     * @return array{data: array<string, mixed>, content: string}
     */
    public static function parseWithContent(string $raw): array
    {
        $pageData = [];
        $content  = $raw;
        if (preg_match(self::PATTERN_WITH_CONTENT, $raw, $matches)) {
            $yaml = Yaml::parse($matches[1]);
            if (is_array($yaml)) {
                $pageData = $yaml;
            }
            $content = $matches[2];
        }

        return ['data' => $pageData, 'content' => $content];
    }

    /**
     * Parse front matter and return only the YAML data.
     *
     * @return array<string, mixed>
     */
    public static function parseDataOnly(string $raw): array
    {
        if (preg_match(self::PATTERN_DATA_ONLY, $raw, $matches)) {
            $yaml = Yaml::parse($matches[1]);
            if (is_array($yaml)) {
                return $yaml;
            }
        }

        return [];
    }
}
