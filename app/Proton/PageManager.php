<?php

namespace App\Proton;

use App\Proton\Settings\Paths;
use Twig\Extra\Markdown\MarkdownExtension;
use Twig\Extra\Markdown\MarkdownRuntime;
use Twig\Extra\Markdown\MichelfMarkdown;
use Twig\Loader\FilesystemLoader;
use Twig\RuntimeLoader\RuntimeLoaderInterface;

class PageManager
{
    public const CACHEDIR = '.proton-cache';
    protected FilesystemLoader $templateLoader;
    protected Paths $paths;

    public function __construct(protected Config $config, protected Data $data, protected FilesystemManager $fsManager)
    {
        $this->paths          = $this->config->settings->paths;
        $this->templateLoader = $this->initTemplateLoader();
    }

    public function compilePages(): void
    {
        $pages = $this->fsManager->getAllFiles($this->paths->pages);
        foreach ($pages as $pageName) {
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
    }

    // public function ksort($array)
    // {
    //     ksort($array);
    //     return $array;
    // }

    private function createPageLoader(string $pageName, string $content): \Twig\Environment
    {
        // Create the Twig Chain Loader
        $loader = new \Twig\Loader\ArrayLoader(["@pages/$pageName" => $content]);
        $loader = new \Twig\Loader\ChainLoader([$loader, $this->templateLoader]);
        // $cache  = new \Twig\Cache\FilesystemCache(self::CACHEDIR, \Twig\Cache\FilesystemCache::FORCE_BYTECODE_INVALIDATION);
        $debug = $this->config->settings->debug;
        $twig  = new \Twig\Environment($loader, [
            'cache' => self::CACHEDIR,
            'debug' => $debug,
        ]);
        if ($debug) {
            $twig->addExtension(new \Twig\Extension\DebugExtension());
        }
        // Markdown Support
        $twig->addExtension(new MarkdownExtension());
        $twig->addRuntimeLoader(new class implements RuntimeLoaderInterface {
            public function load(string $class): ?object
            {
                if ($class === MarkdownRuntime::class) {
                    return new MarkdownRuntime(new MichelfMarkdown());
                }

                return null;
            }
        });

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

    public function refreshData(): void
    {
        $this->data->refresh();
    }
}
