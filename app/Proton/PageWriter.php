<?php

namespace App\Proton;

//---------------------------------------------------------------------------------
// Proton Distributor
//---------------------------------------------------------------------------------
class PageWriter
{
    const OUTPUTKEY = "output";

    protected \Twig\Environment $twig;
    protected Config $config;
    protected Page $page;
    protected string $output;
    protected string $path;

    public function __construct(Page $page, \Twig\Environment $twig, Config $config)
    {
        $this->config = $config;
        $this->page   = $page;
        $this->twig   = $twig;

        $this->output = $this->render($this->page->data);
        $this->formatOutput();

        $this->path = $this->buildPagePath();
    }

    public function savePage(): void
    {
        $dest = $this->config->settings->paths->dist .DIRECTORY_SEPARATOR. $this->path;
        $destDir = dirname($dest);
        if (!file_exists($destDir)) {
            mkdir($destDir, 0777, true);
        }
        file_put_contents($dest, $this->output);
    }

    protected function formatOutput(): void
    {
        if ($this->config->settings->minify) {
            $parser = \WyriHaximus\HtmlCompress\Factory::constructSmallest();
            $this->output = $parser->compress($this->output);
        } elseif ($this->config->settings->pretty) {
            $indenter = new \Gajus\Dindent\Indenter();
            $this->output = $indenter->indent($this->output);
        }
    }

    protected function render(array $data): string
    {
        // Render the page template
        return $this->twig->render("@pages/".$this->page->name, $data);
    }

    protected function buildPagePath(): string
    {
        // If output name defined in page data, return that
        if (array_key_exists(self::OUTPUTKEY, $this->page->data)) {
            return $this->page->data[self::OUTPUTKEY];
        }

        $filePath = [];

        // Directory
        if ($this->page->dirname) {
            array_push($filePath, $this->page->dirname);
        }

        // Filename
        array_push($filePath, $this->page->filename);

        // Auto Index
        if ($this->config->settings->autoindex && !$this->pageIsIndex()) {
            array_push($filePath, "index");
        }

        // Extension
        $ext = $this->findExtension();

        return implode(DIRECTORY_SEPARATOR, $filePath).".$ext";
    }

    protected function pageIsIndex(): bool
    {
        return strstr($this->page->name, "index") ? true : false;
    }

    protected function findExtension(): string
    {
        $ext = $this->page->ext;
        $revertToDefaultExt = ["md", "pug", "twig", null];
        if (in_array($ext, $revertToDefaultExt)) {
            $ext = $this->config->settings->defaultExt;
        }
        return $ext;
    }
}
