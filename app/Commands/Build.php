<?php

namespace App\Commands;

use Illuminate\Console\Scheduling\Schedule;
use LaravelZero\Framework\Commands\Command;
use Aptoma\Twig\Extension\MarkdownExtension;
use Aptoma\Twig\Extension\MarkdownEngine\MichelfMarkdownEngine;
use Symfony\Component\Yaml\Yaml;

const OUTPUTKEY   = "output";
const LAYOUTKEY   = "layout";
const BATCHKEY    = "batch";
const ENDBLOCK    = "endblock";

class Build extends Command
{
    // The signature of the command.
    protected $signature = 'build';

    // The description of the command.
    protected $description = 'Build your site';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {

        //----------------------------------
        // Config Load
        //----------------------------------
        $config = \App\Proton\Config::getConfig();

        //----------------------------------
        // Load in Data
        //----------------------------------
        $data = new \App\Proton\Data($config->paths->data);

        if ($config->debug) {
            $this->info('Proton Collected Data');
            print_r($data->data);
        }

        //----------------------------------
        // Twig FS Loader + FrontMatter
        //----------------------------------
        $fsLoader = new \Twig\Loader\FilesystemLoader([$config->paths->partials, $config->paths->macros]);
        $fsLoader->addPath($config->paths->pages, "pages");
        $fsLoader->addPath($config->paths->layouts, "layouts");
        $frontMatter = new \Webuni\FrontMatter\FrontMatter();

        //----------------------------------
        // Fetch all pages
        //----------------------------------
        $directory = new \RecursiveDirectoryIterator($config->paths->pages);
        $directory->setFlags(\RecursiveDirectoryIterator::SKIP_DOTS);
        $iterator = new \RecursiveIteratorIterator($directory);
        $pages = array();
        // The length of the pages folder name + /
        $dirLength = strlen($config->paths->pages)+1;
        foreach ($iterator as $info) {
            // Remove the pages fodler name from the file name
            $pages[] = substr_replace($info->getPathname(), '', 0, $dirLength);
        }

        //----------------------------------
        // Clear out dist files
        //----------------------------------
        if (file_exists($config->paths->dist)) {
            $directory = new \RecursiveDirectoryIterator($config->paths->dist);
            $directory->setFlags(\RecursiveDirectoryIterator::SKIP_DOTS);
            $iterator = new \RecursiveIteratorIterator($directory);
            foreach ($iterator as $info) {
                unlink($info->getPathname());
                // This leaves empty dirs... should fix eventually
            }
        }

        //----------------------------------
        // Process all pages
        //----------------------------------
        foreach ($pages as $page) {
            // FrontMatter
            $pagePath = $config->paths->pages. DIRECTORY_SEPARATOR .$page;
            $document = file_get_contents($pagePath);
            if (!$document) {
                throw new \Exception("Error reading in page: $page");
            }
            $document = $frontMatter->parse($document);
            $pageData = $document->getData();
            $pageContent = $document->getContent()??"No Content Found";
            $pageExt = pathinfo($pagePath, PATHINFO_EXTENSION);

            // Merge page data with global data
            $pageData = $data->generatePageData($pageData);

            // Default Layout
            $layout = $config->layouts->default;

            // Layout Rules
            foreach ($config->layouts->rules as $ruleMatch => $ruleLayout) {
                if (0 === strpos($page, $ruleMatch)) {
                    $layout = $ruleLayout;
                    break;
                }
            }

            // Assign default content block if none defined in page template
            if (false === strpos($pageContent, ENDBLOCK)) {
                $pageContent = "{% block content %}". $pageContent . "{% endblock %}";
            }

            // Setup page layout unless set to none
            $layout = $pageData[LAYOUTKEY] ?? $layout;
            if ("none" !== $layout) {
                // Assign default content block if none defined in page template
                if (false === strpos($pageContent, ENDBLOCK)) {
                    $pageContent = "{% block content %}". $pageContent . "{% endblock %}";
                }
                $pageContent = "{% extends \"@layouts/$layout\" %}".$pageContent;
            }

            // Auto process markdown pages
            if ("md" === $pageExt) {
                // Start markdown tag after all start blocks
                $pageContent = preg_replace('/\{\%\s+block\s+(\S+)\s+\%\}/', '{% block ${1} %}{% markdown %}', $pageContent)??$pageContent;
                // end markdown tags before all endblocks
                $pageContent = preg_replace('/\{\%\s+endblock\s+\%\}/', '{% endmarkdown %}{% endblock %}', $pageContent)??$pageContent;
            } elseif ("pug" === $pageExt) {
                $pageContent = \Phug\PugToTwig::convert($pageContent);
            }

            // Create the Twig Chain Loader
            $loader = new \Twig\Loader\ArrayLoader([
                "@pages/$page" => $pageContent,
            ]);
            $loader = new \Twig\Loader\ChainLoader([$loader, $fsLoader]);
            $twig = new \Twig\Environment($loader, [
                'cache' => ".proton-cache",
                'debug' => $config->debug
            ]);
            // Markdown Support
            $engine = new MichelfMarkdownEngine();
            $twig->addExtension(new MarkdownExtension($engine));

            $info = pathinfo($page);
            $ext = $info["extension"]??$config->defaultExt;
            $filename = $info["filename"];
            $dirname = $info["dirname"];
            if ("md" === $ext || "pug" === $ext || "twig" === $ext) {
                $ext = $config->defaultExt;
            }
            $filePath = [];
            if ("." !== $dirname) {
                array_push($filePath, $dirname);
            }

            // Output Batch vs Individual
            if (array_key_exists(BATCHKEY, $pageData)) {
                $batch = $pageData[BATCHKEY];
                foreach ($data[$batch] as $batchKey => $batchData) {
                    $pageData[BATCHKEY] = $batchData;
                    $output = $twig->render("@pages/$page", $pageData);

                    if ($config->minify) {
                        $parser = \WyriHaximus\HtmlCompress\Factory::constructSmallest();
                        $output = $parser->compress($output);
                    } elseif ($config->pretty) {
                        $indenter = new \Gajus\Dindent\Indenter();
                        $output = $indenter->indent($output);
                    }

                    $batchPath = $filePath;
                    array_push($batchPath, $batchKey);

                    if ($config->autoindex) {
                        array_push($batchPath, "index");
                    }
                    $pageOut = implode(DIRECTORY_SEPARATOR, $batchPath).".$ext";

                    $dest = $config->paths->dist . DIRECTORY_SEPARATOR . $pageOut;
                    $destDir = dirname($dest);
                    if (!file_exists($destDir)) {
                        mkdir($destDir, 0777, true);
                    }
                    file_put_contents($dest, $output);
                }
            } else {
                // Render the page template
                $output = $twig->render("@pages/$page", $pageData);

                if ($config->minify) {
                    $parser = \WyriHaximus\HtmlCompress\Factory::constructSmallest();
                    $output = $parser->compress($output);
                } elseif ($config->pretty) {
                    $indenter = new \Gajus\Dindent\Indenter();
                    $output = $indenter->indent($output);
                }

                array_push($filePath, $filename);

                // Auto Index
                if ($config->autoindex && !strstr($page, "index")) {
                    array_push($filePath, "index");
                }

                $page = implode(DIRECTORY_SEPARATOR, $filePath).".$ext";

                // Custom Destination in FrontMatter
                if (array_key_exists(OUTPUTKEY, $pageData)) {
                    // replace page name with new dest name
                    $page = $pageData[OUTPUTKEY];
                }

                $dest = $config->paths->dist . DIRECTORY_SEPARATOR . $page;
                $destDir = dirname($dest);
                if (!file_exists($destDir)) {
                    mkdir($destDir, 0777, true);
                }
                file_put_contents($dest, $output);
            }
        }

        $this->info('Build Complete.');
    }

    /**
     * Define the command's schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    public function schedule(Schedule $schedule): void
    {
        // $schedule->command(static::class)->everyMinute();
    }
}
