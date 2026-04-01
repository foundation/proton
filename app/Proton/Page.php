<?php

namespace App\Proton;

// ---------------------------------------------------------------------------------
// Proton Page
// ---------------------------------------------------------------------------------
class Page
{
    public const NOLAYOUT  = 'none';
    public const ENDBLOCK  = 'endblock';
    public const LAYOUTKEY = 'layout';
    public const BATCHKEY  = 'batch';
    public const OUTPUTKEY = 'output';

    /** @var array<string, mixed> */
    public array $data;
    public string $content;
    public string $filename;
    public ?string $ext;
    public ?string $dirname;

    public function __construct(public string $name, protected Config $config, Data $data)
    {
        $info           = pathinfo($this->name);
        $this->filename = $info['filename'];
        $this->ext      = $info['extension'] ?? null;
        $this->dirname  = !isset($info['dirname']) || '.' === $info['dirname'] ? null : $info['dirname'];

        // Setup $data + $content
        $this->processPage($data);
        // Inject computed page metadata (frontmatter values take precedence)
        $this->injectPageMetadata();
        // Apply Layout macros
        $this->applyLayout();
        $this->formatMarkdown();
        $this->formatRaw();
    }

    public function isBatch(): bool
    {
        return array_key_exists(self::BATCHKEY, $this->data['page']);
    }

    public function getPageData(string $key): mixed
    {
        if (array_key_exists($key, $this->data['page'])) {
            return $this->data['page'][$key];
        }

        return null;
    }

    public function getProtonData(string $key): mixed
    {
        if (array_key_exists($key, $this->data['proton'])) {
            return $this->data['proton'][$key];
        }

        return null;
    }

    public function getData(string $key): mixed
    {
        if (array_key_exists($key, $this->data['data'])) {
            return $this->data['data'][$key];
        }

        return null;
    }

    public function dumpData(): void
    {
        print_r($this->data);
    }

    private function processPage(Data $data): void
    {
        $path = $this->config->settings->paths->pages . DIRECTORY_SEPARATOR . $this->name;
        if (!file_exists($path)) {
            throw new Exceptions\BuildException("Error reading in page: $path");
        }
        $raw = file_get_contents($path);
        if ($raw === false) {
            throw new Exceptions\BuildException("Error reading in page: $path");
        }

        $result   = FrontMatterParser::parseWithContent($raw);
        $pageData = $result['data'];
        $content  = $result['content'];

        $this->data    = $data->generatePageData($pageData);
        $this->content = $content ?: 'No Content Found';
    }

    /**
     * @param array<string, mixed> $pageData
     */
    public static function buildUrl(
        string $filename,
        ?string $dirname,
        ?string $ext,
        array $pageData,
        Config $config,
    ): string {
        // If output name defined in page data, use that
        if (isset($pageData[self::OUTPUTKEY])) {
            return '/' . ltrim($pageData[self::OUTPUTKEY], '/');
        }

        $parts = [];
        if ($dirname) {
            $parts[] = $dirname;
        }

        $isIndex   = $filename === 'index';
        $autoindex = $config->settings->autoindex;

        if ($autoindex && !$isIndex) {
            // autoindex: /dir/page/ (clean URL)
            $parts[] = $filename;

            return '/' . implode('/', $parts) . '/';
        }

        // Non-autoindex or index pages
        $parts[] = $filename;

        // Resolve extension
        $outputExt = in_array($ext, PageWriter::TEMPLATE_EXTENSIONS, true)
            ? $config->settings->defaultExt
            : ($ext ?? $config->settings->defaultExt);

        $path = '/' . implode('/', $parts) . '.' . $outputExt;

        // For index files, provide clean URL: /dir/ instead of /dir/index.html
        if ($isIndex) {
            $path = '/' . ($dirname ? $dirname . '/' : '');
        }

        return $path;
    }

    /**
     * @param array<string, mixed> $pageData
     */
    public static function buildOutputPath(
        string $filename,
        ?string $dirname,
        ?string $ext,
        array $pageData,
        Config $config,
    ): string {
        if (isset($pageData[self::OUTPUTKEY])) {
            return $pageData[self::OUTPUTKEY];
        }

        $parts = [];
        if ($dirname) {
            $parts[] = $dirname;
        }
        $parts[] = $filename;

        if ($config->settings->autoindex && $filename !== 'index') {
            $parts[] = 'index';
        }

        $outputExt = in_array($ext, PageWriter::TEMPLATE_EXTENSIONS, true)
            ? $config->settings->defaultExt
            : ($ext ?? $config->settings->defaultExt);

        return implode('/', $parts) . '.' . $outputExt;
    }

    /**
     * @param array<string, mixed> $pageData
     */
    public static function computeMetadata(
        string $name,
        string $filename,
        ?string $dirname,
        ?string $ext,
        array $pageData,
        Config $config,
    ): array {
        $url        = $pageData['url'] ?? self::buildUrl($filename, $dirname, $ext, $pageData, $config);
        $domain     = rtrim($config->settings->domain, '/');
        $isIndex    = $filename === 'index';
        $depth      = $dirname ? substr_count($dirname, '/') + 1 : 0;

        return [
            'url'        => $url,
            'canonical'  => $domain . $url,
            'title'      => $pageData['title'] ?? ucfirst(str_replace('-', ' ', $filename)),
            'filename'   => $filename,
            'dirname'    => $dirname,
            'ext'        => $ext,
            'path'       => $name,
            'outputPath' => self::buildOutputPath($filename, $dirname, $ext, $pageData, $config),
            'depth'      => $depth,
            'isIndex'    => $isIndex,
            'parent'     => $dirname ? '/' . $dirname . '/' : '/',
            'slug'       => strtolower((string) preg_replace('/[^a-z0-9]+/i', '-', $filename)),
        ];
    }

    private function injectPageMetadata(): void
    {
        $page = &$this->data['page'];

        $computed = self::computeMetadata(
            $this->name,
            $this->filename,
            $this->dirname,
            $this->ext,
            $page,
            $this->config,
        );

        // Frontmatter values take precedence over computed values
        foreach ($computed as $key => $value) {
            if (!isset($page[$key])) {
                $page[$key] = $value;
            }
        }

        // These are always set from source, not overridable
        $page['filename'] = $this->filename;
        $page['dirname']  = $this->dirname;
        $page['ext']      = $this->ext;
        $page['path']     = $this->name;
    }

    private function applyLayout(): void
    {
        // Local page Data vs Layout Rules vs Default
        $layout = $this->getPageData(self::LAYOUTKEY) ??
                  $this->findLayoutRule() ??
                  $this->config->settings->layouts->default;

        // Setup page layout unless set to none
        if (self::NOLAYOUT !== $layout) {
            // Content Block Wrapper
            $this->addContentBlockWrapper();
            $this->addContentLayout($layout);
        }
    }

    private function addContentLayout(string $layout): void
    {
        $this->content = "{% extends \"@layouts/$layout\" %}" . $this->content;
    }

    private function addContentBlockWrapper(): void
    {
        // Assign default content block if none defined in page template
        if (!str_contains($this->content, self::ENDBLOCK)) {
            $this->content = '{% block content %}' . $this->content . '{% endblock %}';
        }
    }

    private function findLayoutRule(): ?string
    {
        foreach ($this->config->settings->layouts->rules as $ruleMatch => $ruleLayout) {
            if (str_starts_with($this->name, $ruleMatch)) {
                return $ruleLayout;
            }
        }

        return null;
    }

    private function formatMarkdown(): void
    {
        if ('md' === $this->ext) {
            $raw = $this->getPageData('raw') === true;

            if ($raw) {
                // In raw mode, the entire content is treated as literal markdown.
                // Extract the {% extends %} tag (added by applyLayout) before wrapping,
                // then strip block tags and wrap content in verbatim + markdown.
                $extends = '';
                if (preg_match('/^(\{\%\s+extends\s+[^%]+\%\})/', $this->content, $m)) {
                    $extends       = $m[1];
                    $this->content = substr($this->content, strlen($m[0]));
                }
                $this->content = preg_replace('/\{\%\s+block\s+\S+\s+\%\}/', '', $this->content) ?? $this->content;
                $this->content = preg_replace('/\{\%\s+endblock\s+\%\}/', '', $this->content) ?? $this->content;
                $this->content = $extends
                    . '{% block content %}{% apply markdown_to_html %}{% verbatim %}'
                    . $this->content
                    . '{% endverbatim %}{% endapply %}{% endblock %}';
            } else {
                // Start markdown filter after all start blocks
                $this->content = preg_replace('/\{\%\s+block\s+(\S+)\s+\%\}/', '{% block ${1} %}{% apply markdown_to_html %}', $this->content) ?? $this->content;
                // End markdown filter before all endblocks
                $this->content = preg_replace('/\{\%\s+endblock\s+\%\}/', '{% endapply %}{% endblock %}', $this->content) ?? $this->content;
            }
        }
    }

    private function formatRaw(): void
    {
        // For non-markdown files, wrap block content in verbatim when raw: true
        if ('md' !== $this->ext && $this->getPageData('raw') === true) {
            $this->content = preg_replace('/\{\%\s+block\s+(\S+)\s+\%\}/', '{% block ${1} %}{% verbatim %}', $this->content) ?? $this->content;
            $this->content = preg_replace('/\{\%\s+endblock\s+\%\}/', '{% endverbatim %}{% endblock %}', $this->content) ?? $this->content;
        }
    }
}
