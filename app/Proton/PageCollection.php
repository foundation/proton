<?php

namespace App\Proton;

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

            $raw      = file_get_contents($path);
            $pageData = $raw !== false ? FrontMatterParser::parseDataOnly($raw) : [];

            $info     = pathinfo($pageName);
            $filename = $info['filename'];
            $ext      = $info['extension'] ?? null;
            $dirname  = !isset($info['dirname']) || '.' === $info['dirname'] ? null : $info['dirname'];

            $computed = Page::computeMetadata($pageName, $filename, $dirname, $ext, $pageData, $config);

            // Frontmatter values take precedence over computed values
            $entry = array_merge($computed, $pageData);

            // Computed structural fields are always authoritative
            $entry['filename'] = $filename;
            $entry['dirname']  = $dirname;
            $entry['ext']      = $ext;
            $entry['path']     = $pageName;

            $pages[] = $entry;
        }

        return $pages;
    }
}
