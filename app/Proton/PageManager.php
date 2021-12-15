<?php

namespace App\Proton;

use \Twig\Loader\FilesystemLoader;
use Aptoma\Twig\Extension\MarkdownExtension;
use Aptoma\Twig\Extension\MarkdownEngine\MichelfMarkdownEngine;

//---------------------------------------------------------------------------------
// Proton PageManager
//---------------------------------------------------------------------------------
class PageManager
{
    const CACHEDIR = ".proton-cache";

    protected Config $config;
    protected Data $data;
    protected FilesystemLoader $templateLoader;

    /** @var mixed $paths */
    protected $paths;

    public function __construct(Config $config, Data $data)
    {
        $this->config         = $config;
        $this->paths          = $config->settings->paths;
        $this->data           = $data;
        $this->templateLoader = $this->initTemplateLoader();
    }

    public function compilePages(): void
    {
        foreach ($this->getAllPages() as $pageName) {
            $page = new Page($pageName, $this->config, $this->data);
            $loader = $this->createPageLoader($pageName, $page->content);
            if ($page->isBatch()) {
                $bathWriter = new PageBatchWriter($page, $loader, $this->config);
                $bathWriter->processBatch();
            } else {
                $pageWriter = new PageWriter($page, $loader, $this->config);
                $pageWriter->savePage();
            }
        }
    }

    private function createPageLoader(string $pageName, string $content): \Twig\Environment
    {
        // Create the Twig Chain Loader
        $loader = new \Twig\Loader\ArrayLoader(["@pages/$pageName" => $content]);
        $loader = new \Twig\Loader\ChainLoader([$loader, $this->templateLoader]);
        $twig = new \Twig\Environment($loader, [
            'cache' => self::CACHEDIR,
            'debug' => $this->config->settings->debug
        ]);
        // Markdown Support
        $twig->addExtension(new MarkdownExtension(new MichelfMarkdownEngine()));
        return $twig;
    }

    private function initTemplateLoader(): FilesystemLoader
    {
        $loader = new FilesystemLoader([
            $this->paths->partials,
            $this->paths->macros
        ]);
        $loader->addPath($this->paths->pages, "pages");
        $loader->addPath($this->paths->layouts, "layouts");
        return $loader;
    }

    private function getAllPages(): array
    {
        $directory = new \RecursiveDirectoryIterator($this->paths->pages);
        $directory->setFlags(\RecursiveDirectoryIterator::SKIP_DOTS);
        $iterator = new \RecursiveIteratorIterator($directory);
        $pages = [];
        // The length of the pages folder name + /
        $dirLength = strlen($this->paths->pages)+1;
        foreach ($iterator as $info) {
            // Remove the pages fodler name from the file name
            $pages[] = substr_replace($info->getPathname(), '', 0, $dirLength);
        }
        return $pages;
    }
}
