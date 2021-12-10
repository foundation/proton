<?php

use Symfony\Component\Yaml\Yaml;

require_once 'vendor/autoload.php';

const CONFIGFILES = [".proton", "proton.yml"];
const DEFAULTDATA = "data";

//----------------------------------
// Config Load
//----------------------------------
$config = [
    "paths" => [
        "batch"    => "batch",
        "helpers"  => "helpers",
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
$directory->setFlags(RecursiveDirectoryIterator::SKIP_DOTS);
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
// Init Twig Environment
//----------------------------------
$frontMatter = \Webuni\FrontMatter\Twig\TwigCommentFrontMatter::create();
$loader = new \Twig\Loader\FilesystemLoader([$config->paths->partials]);
$loader->addPath($config->paths->pages, "pages");
$loader->addPath($config->paths->layouts, "layouts");
$converter = \Webuni\FrontMatter\Twig\DataToTwigConvertor::vars();
$loader = new \Webuni\FrontMatter\Twig\FrontMatterLoader($frontMatter, $loader, $converter);
$twig = new \Twig\Environment($loader, [
    'cache' => "proton-cache",
    'debug' => $config->debug
]);

//----------------------------------
// Fetch all pages
//----------------------------------
$directory = new \RecursiveDirectoryIterator($config->paths->pages);
$directory->setFlags(RecursiveDirectoryIterator::SKIP_DOTS);
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
    $directory->setFlags(RecursiveDirectoryIterator::SKIP_DOTS);
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
    $layout = $twig->load('@layouts/second.html');
    $data["layout"] = $layout;
    // $twig->display("@pages/$page", $data);

    $output = $twig->render("@pages/$page", ['layout' => $layout]);

    $dest = $config->paths->dist . DIRECTORY_SEPARATOR . $page;
    $destDir = dirname($dest);
    if (!file_exists($destDir)) {
        mkdir($destDir, 0777, true);
    }
    file_put_contents($dest, $output);
}
