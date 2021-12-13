<?php

namespace App\Commands;

use Illuminate\Console\Scheduling\Schedule;
use LaravelZero\Framework\Commands\Command;
use Symfony\Component\Yaml\Yaml;

const CONFIGFILES = [".proton", "proton.yml"];
const DEFAULTDATA = "data";
const OUTPUTDEST  = "output";

class Build extends Command
{
    /**
     * The signature of the command.
     *
     * @var string
     */
    protected $signature = 'build';

    /**
     * The description of the command.
     *
     * @var string
     */
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
        $config = [
            "paths" => [
                "batch"    => "batch",
                "macros"   => "macros",
                "pages"    => "pages",
                "partials" => "partials",
                "layouts"  => "layouts",
                "data"     => "data",
                "dist"     => "dist",
            ],
            "debug"   => false,
            "minify"  => false,
            "layouts" => [
                "default" => "default.html",
                "rules" => [
                    "blog" => "blog.html"
                ]
            ]
        ];
        // Config file override
        foreach (CONFIGFILES as $configFile) {
            if (file_exists($configFile)) {
                $config = array_merge($config, Yaml::parseFile($configFile));
            }
        }
        // Make it an object
        $config = json_decode((string) json_encode($config));

        //----------------------------------
        // Load in Data
        //----------------------------------
        $data = [];
        $directory = new \RecursiveDirectoryIterator($config->paths->data);
        $directory->setFlags(\RecursiveDirectoryIterator::SKIP_DOTS);
        $iterator = new \RecursiveIteratorIterator($directory);
        // The length of the data folder name + /
        $dirLength = strlen($config->paths->data)+1;
        foreach ($iterator as $info) {
            $newData = Yaml::parseFile($info->getPathname());

            // Remove the data folder name from the path
            $dataPath = substr_replace((string) $info->getPathname(), '', 0, $dirLength);

            // Remove the extension
            $extLength = strlen($info->getExtension())+1;
            $dataPath = substr_replace($dataPath, '', $extLength*-1, $extLength);

            // If default data file, add it to root of data
            if (DEFAULTDATA === $dataPath) {
                $data = array_merge($data, $newData);
                continue;
            }

            // Get hierarchy of the data path
            $parts = explode(DIRECTORY_SEPARATOR, $dataPath);

            // Dynamically setup the same heirarchy in the data
            $temp = &$data;
            foreach ($parts as $key) {
                $temp = &$temp[$key];
            }
            $temp = $newData;
            unset($temp);
        }

        if ($config->debug) {
            echo "Proton Collected Data:".PHP_EOL;
            print_r($data);
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
            $pageContent = $document->getContent();

            // Merge page data with global data
            $pageData = array_merge($data, $pageData);

            // Setup page layout unless set to none
            $layout = $pageData["layout"] ?? $config->layouts->default;
            if ("none" !== $layout) {
                $pageContent = "{% extends \"@layouts/$layout\" %}".$pageContent;
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
            // Render the page template
            $output = $twig->render("@pages/$page", $pageData);

            // Custom Destination in FrontMatter
            if (array_key_exists(OUTPUTDEST, $pageData)) {
                // replace page name with new dest name
                $page = $pageData[OUTPUTDEST];
            }

            $dest = $config->paths->dist . DIRECTORY_SEPARATOR . $page;
            $destDir = dirname($dest);
            if (!file_exists($destDir)) {
                mkdir($destDir, 0777, true);
            }
            file_put_contents($dest, $output);
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
