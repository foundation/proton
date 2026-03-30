<?php

namespace App\Proton;

use App\Proton\Settings\Paths;
use Twig\Extra\Markdown\MarkdownExtension;
use Twig\Extra\Markdown\MarkdownRuntime;
use Twig\Loader\FilesystemLoader;
use Twig\RuntimeLoader\RuntimeLoaderInterface;

class PageManager
{
    public const CACHEDIR = '.proton-cache';
    protected FilesystemLoader $templateLoader;
    protected Paths $paths;
    public function __construct(
        protected Config $config,
        protected Data $data,
        protected FilesystemManager $fsManager,
        protected MarkdownConverter $markdownConverter = new MarkdownConverter(),
    ) {
        $this->paths          = $this->config->settings->paths;
        $this->templateLoader = $this->initTemplateLoader();
    }

    public function compilePages(): int
    {
        $pages = $this->fsManager->getAllFiles($this->paths->pages);

        if ($this->data->env['environment'] !== 'development') {
            $pages = $this->filterDrafts($pages);
        }

        $this->data->setPages(PageCollection::collect($pages, $this->config));
        foreach ($pages as $pageName) {
            $this->markdownConverter->resetToc();
            $page   = new Page($pageName, $this->config, $this->data);
            $loader = $this->createPageLoader($pageName, $page->content);
            if ($page->isBatch()) {
                $bathWriter = new PageBatchWriter($page, $loader, $this->config);
                $bathWriter->processBatch();
            } else {
                $pageWriter = new PageWriter($page, $loader, $this->config);
                $pageWriter->savePage();
            }
        }

        return count($pages);
    }

    private function createPageLoader(string $pageName, string $content): \Twig\Environment
    {
        // Create the Twig Chain Loader
        $loader = new \Twig\Loader\ArrayLoader(["@pages/$pageName" => $content]);
        $loader = new \Twig\Loader\ChainLoader([$loader, $this->templateLoader]);
        $debug  = $this->config->settings->debug;
        $twig   = new \Twig\Environment($loader, [
            'cache' => self::CACHEDIR,
            'debug' => $debug,
        ]);
        if ($debug) {
            $twig->addExtension(new \Twig\Extension\DebugExtension());
        }
        // Markdown Support
        $twig->addExtension(new MarkdownExtension());
        $converter = $this->markdownConverter;
        $twig->addRuntimeLoader(new class($converter) implements RuntimeLoaderInterface {
            public function __construct(private MarkdownConverter $converter)
            {
            }

            public function load(string $class): ?object
            {
                if ($class === MarkdownRuntime::class) {
                    return new MarkdownRuntime($this->converter);
                }

                return null;
            }
        });

        // TOC function — returns headings extracted during markdown rendering
        $twig->addFunction(new \Twig\TwigFunction('toc', fn (): array => $converter->getToc()));

        // ksort the twig variables
        $filter = new \Twig\TwigFilter('ksort', function ($array): array {
            ksort($array);

            return $array;
        });
        $twig->addFilter($filter);

        // krsort the twig variables
        $filter = new \Twig\TwigFilter('krsort', function ($array): array {
            krsort($array);

            return $array;
        });
        $twig->addFilter($filter);

        // count the twig variables
        $filter = new \Twig\TwigFilter('count', fn ($array): int => count($array));
        $twig->addFilter($filter);

        return $twig;
    }

    private function initTemplateLoader(): FilesystemLoader
    {
        $loader = new FilesystemLoader([
            $this->paths->partials,
            $this->paths->macros,
        ]);
        $loader->addPath($this->paths->pages, 'pages');
        $loader->addPath($this->paths->layouts, 'layouts');

        return $loader;
    }

    /**
     * @param string[] $pages
     * @return string[]
     */
    private function filterDrafts(array $pages): array
    {
        $pagesDir = $this->paths->pages;

        return array_values(array_filter($pages, function (string $pageName) use ($pagesDir): bool {
            $path = $pagesDir . DIRECTORY_SEPARATOR . $pageName;
            $raw  = file_get_contents($path);
            if ($raw !== false) {
                $data = FrontMatterParser::parseDataOnly($raw);
                if (($data['draft'] ?? false) === true) {
                    return false;
                }
            }

            return true;
        }));
    }

    public function refreshData(): void
    {
        $this->data->refresh();
    }
}
