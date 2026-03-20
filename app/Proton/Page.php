<?php

namespace App\Proton;

//---------------------------------------------------------------------------------
// Proton Page
//---------------------------------------------------------------------------------
class Page
{
    const NOLAYOUT  = "none";
    const ENDBLOCK  = "endblock";
    const LAYOUTKEY = "layout";
    const BATCHKEY  = "batch";
    const OUTPUTKEY = "output";

    protected Config $config;

    public array   $data;
    public string  $name;
    public string  $content;
    public string  $filename;
    public ?string $ext;
    public ?string $dirname;

    public function __construct(string $name, Config $config, Data $data)
    {
        $this->config = $config;
        $this->name   = $name;

        $info = pathinfo($name);
        $this->filename = $info["filename"];
        $this->ext      = $info["extension"]??null;
        $this->dirname  = "." === $info["dirname"] ? null : $info["dirname"];

        // Setup $data + $content
        $this->processPage($data);
        // Apply Layout macros
        $this->applyLayout();
        $this->formatMarkdown();
        $this->formatRaw();
        $this->formatPug();
    }

    public function isBatch(): bool
    {
        return array_key_exists(self::BATCHKEY, $this->data["page"]);
    }

    public function getPageData($key)
    {
        if (array_key_exists($key, $this->data["page"])) {
            return $this->data["page"][$key];
        }
        return null;
    }

    public function getProtonData($key)
    {
        if (array_key_exists($key, $this->data["proton"])) {
            return $this->data["proton"][$key];
        }
        return null;
    }

    public function getData($key)
    {
        if (array_key_exists($key, $this->data["data"])) {
            return $this->data["data"][$key];
        }
        return null;
    }

    public function dumpData(): void
    {
        print_r($this->data);
    }

    private function processPage(Data $data): void
    {
        $path = $this->config->settings->paths->pages. DIRECTORY_SEPARATOR .$this->name;
        $raw = file_get_contents($path);
        if (!$raw) {
            throw new \Exception("Error reading in page: $path");
        }

        // Parse YAML front matter (--- delimited)
        $pageData = [];
        $content = $raw;
        if (preg_match('/\A---\s*\n(.*?)\n---\s*\n(.*)\z/s', $raw, $matches)) {
            $yaml = \Symfony\Component\Yaml\Yaml::parse($matches[1]);
            if (is_array($yaml)) {
                $pageData = $yaml;
            }
            $content = $matches[2];
        }

        $this->data = $data->generatePageData($pageData);
        $this->content = $content ?: "No Content Found";
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
        $this->content = "{% extends \"@layouts/$layout\" %}".$this->content;
    }

    private function addContentBlockWrapper(): void
    {
        // Assign default content block if none defined in page template
        if (false === strpos($this->content, self::ENDBLOCK)) {
            $this->content = "{% block content %}". $this->content . "{% endblock %}";
        }
    }

    private function findLayoutRule(): ?string
    {
        $rules = $this->config->settings->layouts->rules;
        foreach ($rules as $ruleMatch => $ruleLayout) {
            if (0 === strpos($this->name, $ruleMatch)) {
                return $ruleLayout;
            }
        }
        return null;
    }

    private function formatMarkdown(): void
    {
        if ("md" === $this->ext) {
            $raw = $this->getPageData('raw') === true;

            if ($raw) {
                // In raw mode, the entire content is treated as literal markdown.
                // We strip any user-defined {% block %} tags (they'd conflict with verbatim)
                // and wrap the whole content block in verbatim + markdown.
                $this->content = preg_replace('/\{\%\s+block\s+\S+\s+\%\}/', '', $this->content)??$this->content;
                $this->content = preg_replace('/\{\%\s+endblock\s+\%\}/', '', $this->content)??$this->content;
                $this->content = "{% block content %}{% apply markdown_to_html %}{% verbatim %}"
                    . $this->content
                    . "{% endverbatim %}{% endapply %}{% endblock %}";
            } else {
                // Start markdown filter after all start blocks
                $this->content = preg_replace('/\{\%\s+block\s+(\S+)\s+\%\}/', '{% block ${1} %}{% apply markdown_to_html %}', $this->content)??$this->content;
                // End markdown filter before all endblocks
                $this->content = preg_replace('/\{\%\s+endblock\s+\%\}/', '{% endapply %}{% endblock %}', $this->content)??$this->content;
            }
        }
    }

    private function formatRaw(): void
    {
        // For non-markdown files, wrap block content in verbatim when raw: true
        if ("md" !== $this->ext && $this->getPageData('raw') === true) {
            $this->content = preg_replace('/\{\%\s+block\s+(\S+)\s+\%\}/', '{% block ${1} %}{% verbatim %}', $this->content)??$this->content;
            $this->content = preg_replace('/\{\%\s+endblock\s+\%\}/', '{% endverbatim %}{% endblock %}', $this->content)??$this->content;
        }
    }

    private function formatPug(): void
    {
        // Pug support removed — pug/twig package is not PHP 8.4 compatible
    }
}
