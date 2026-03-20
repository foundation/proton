<?php

namespace App\Proton;

// ---------------------------------------------------------------------------------
// Proton Distributor
// ---------------------------------------------------------------------------------
class PageWriter
{
    protected string $output;
    protected string $path;

    public function __construct(protected Page $page, protected \Twig\Environment $twig, protected Config $config)
    {
        $this->output = $this->render($this->page->data);
        $this->formatOutput();

        $this->path = $this->buildPagePath();
    }

    public function savePage(): void
    {
        $dest    = $this->config->settings->paths->dist . DIRECTORY_SEPARATOR . $this->path;
        $destDir = dirname($dest);
        if (!file_exists($destDir) && !mkdir($destDir, 0777, true)) {
            throw new Exceptions\FilesystemException("Failed to create directory: $destDir");
        }
        $result = file_put_contents($dest, $this->output);
        if ($result === false) {
            throw new Exceptions\FilesystemException("Failed to write page: $dest");
        }
    }

    protected function formatOutput(): void
    {
        if ($this->config->settings->minify) {
            $parser       = \WyriHaximus\HtmlCompress\Factory::constructSmallest();
            $this->output = $parser->compress($this->output);
        } elseif ($this->config->settings->pretty) {
            $indenter = new \Gajus\Dindent\Indenter();
            // Set headers to inline style for nicer pretty output
            $inline = ['h1', 'h2', 'h3', 'h4', 'h5', 'h6'];
            foreach ($inline as $tag) {
                $indenter->setElementType($tag, \Gajus\Dindent\Indenter::ELEMENT_TYPE_INLINE); // @phpstan-ignore argument.type (library PHPDoc incorrectly types the constant)
            }
            $this->output = $indenter->indent($this->output);
        }
    }

    /**
     * @param array<string, mixed> $data
     */
    protected function render(array $data): string
    {
        // Render the page template
        return $this->twig->render('@pages/' . $this->page->name, $data);
    }

    protected function buildPagePath(): string
    {
        // If output name defined in page data, return that
        $name = $this->page->getPageData(Page::OUTPUTKEY);
        if ($name) {
            return $name;
        }

        $filePath = [];

        // Directory
        if ($this->page->dirname) {
            $filePath[] = $this->page->dirname;
        }

        // Filename
        $filePath[] = $this->page->filename;

        // Auto Index
        if ($this->config->settings->autoindex && !$this->pageIsIndex()) {
            $filePath[] = 'index';
        }

        // Extension
        $ext = $this->findExtension();

        return implode(DIRECTORY_SEPARATOR, $filePath) . ".$ext";
    }

    protected function pageIsIndex(): bool
    {
        return (bool)strstr($this->page->name, 'index');
    }

    protected function findExtension(): string
    {
        $ext                = $this->page->ext;
        $revertToDefaultExt = ['md', 'pug', 'twig', null];
        if (in_array($ext, $revertToDefaultExt, true)) {
            return $this->config->settings->defaultExt;
        }

        return $ext ?? $this->config->settings->defaultExt;
    }
}
