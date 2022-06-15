<?php

namespace App\Proton;

use Symfony\Component\Yaml\Yaml;

//---------------------------------------------------------------------------------
// Proton Configuration
//---------------------------------------------------------------------------------
class Data
{
    const DEFAULTDATA = "data";

    public array  $data = [];
    public array  $env = [];
    public string $dir;

    public function __construct(Config $config)
    {
        $this->dir = $config->settings->paths->data;
        $this->initData();
    }

    public function dump(): void
    {
        print_r($this->data);
    }

    public function refresh(): void
    {
        $this->data = [];
        $this->env  = [];
        $this->initData();
    }

    private function initData(): void
    {
        $this->initDataFiles();
        $this->initEnvData();
    }

    private function initEnvData(): void
    {
        $this->env = [
            'environment' => getenv('PROTON_ENV')??"development",
        ];
    }

    private function initDataFiles(): void
    {
        $directory = new \RecursiveDirectoryIterator($this->dir);
        $directory->setFlags(\RecursiveDirectoryIterator::SKIP_DOTS);
        $iterator = new \RecursiveIteratorIterator($directory);

        foreach ($iterator as $file) {
            $this->mergeDataFile($file);
        }
    }

    private function mergeDataFile(\SplFileInfo $file): void
    {
        $fileData = Yaml::parseFile($file->getPathname());
        $dataPath = $this->getDataPath($file);

        // If default data file, add it to root of data
        if (self::DEFAULTDATA === $dataPath) {
            $this->data = array_merge($this->data, $fileData);
            return;
        }

        // Get hierarchy of the data path
        $parts = explode(DIRECTORY_SEPARATOR, $dataPath);

        // Dynamically setup the same heirarchy in the data
        $temp = &$this->data;
        foreach ($parts as $key) {
            $temp = &$temp[$key];
        }
        $temp = $fileData;
        unset($temp);
    }

    // Generate the path heirarchy for the data based on the heirarchy of the file path
    public function getDataPath(\SplFileInfo $file): string
    {
        // Remove the data folder name from the path
        $dirLength = strlen($this->dir)+1; // The length of the data folder name + 1
        $dataPath  = substr_replace((string) $file->getPathname(), '', 0, $dirLength);

        // Remove the extension
        $extLength = strlen($file->getExtension())+1;
        $dataPath = substr_replace($dataPath, '', $extLength*-1, $extLength);

        return $dataPath;
    }

    public function generatePageData(array $pageData): array
    {
        return [
            "data"   => $this->data,
            "proton" => $this->env,
            "page"   => $pageData,
        ];
    }
}
