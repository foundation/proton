<?php

namespace App\Proton;

use Symfony\Component\Yaml\Yaml;

class PageCollection
{
    /**
     * @param string[] $pageNames
     *
     * @return array<int, array<string, mixed>>
     */
    public static function collect(array $pageNames, Config $config): array
    {
        $pages    = [];
        $pagesDir = $config->settings->paths->pages;

        foreach ($pageNames as $pageName) {
            $path = $pagesDir . DIRECTORY_SEPARATOR . $pageName;
            if (!file_exists($path)) {
                continue;
            }

            $raw       = file_get_contents($path);
            $pageData  = [];

            // Parse YAML front matter
            if ($raw !== false && preg_match('/\A---\s*\n(.*?)\n---\s*\n/s', $raw, $matches)) {
                $yaml = Yaml::parse($matches[1]);
                if (is_array($yaml)) {
                    $pageData = $yaml;
                }
            }

            $info     = pathinfo($pageName);
            $filename = $info['filename'];
            $ext      = $info['extension'] ?? null;
            $dirname  = !isset($info['dirname']) || '.' === $info['dirname'] ? null : $info['dirname'];

            $url = self::buildUrl($filename, $dirname, $ext, $pageData, $config);

            $entry = array_merge($pageData, [
                'url'      => $url,
                'filename' => $filename,
                'dirname'  => $dirname,
                'ext'      => $ext,
            ]);

            // Ensure title falls back to filename
            if (!isset($entry['title'])) {
                $entry['title'] = ucfirst(str_replace('-', ' ', $filename));
            }

            $pages[] = $entry;
        }

        return $pages;
    }

    private static function buildUrl(
        string $filename,
        ?string $dirname,
        ?string $ext,
        array $pageData,
        Config $config,
    ): string {
        // If output name defined in page data, use that
        if (isset($pageData[Page::OUTPUTKEY])) {
            return '/' . ltrim($pageData[Page::OUTPUTKEY], '/');
        }

        $parts = [];
        if ($dirname) {
            $parts[] = $dirname;
        }

        $isIndex  = str_contains($filename, 'index');
        $autoindex = $config->settings->autoindex;

        if ($autoindex && !$isIndex) {
            // autoindex: /dir/page/ (clean URL)
            $parts[] = $filename;

            return '/' . implode('/', $parts) . '/';
        }

        // Non-autoindex or index pages
        $parts[] = $filename;

        // Resolve extension
        $revertToDefaultExt = ['md', 'pug', 'twig', null];
        $outputExt          = in_array($ext, $revertToDefaultExt, true)
            ? $config->settings->defaultExt
            : ($ext ?? $config->settings->defaultExt);

        $path = '/' . implode('/', $parts) . '.' . $outputExt;

        // For index files, provide clean URL: /dir/ instead of /dir/index.html
        if ($isIndex) {
            $path = '/' . ($dirname ? $dirname . '/' : '');
        }

        return $path;
    }
}
