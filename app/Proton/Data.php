<?php

namespace App\Proton;

use App\Proton\Exceptions\ConfigException;
use Symfony\Component\Yaml\Yaml;

/** @phpstan-type DataArray array<string, mixed> */

// ---------------------------------------------------------------------------------
// Proton Configuration
// ---------------------------------------------------------------------------------
class Data
{
    public const DEFAULTDATA = 'data';

    /** @var array<string, mixed> */
    public array $data = [];
    /** @var array<string, mixed> */
    public array $env  = [];
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
            'environment' => getenv('PROTON_ENV') ?: 'development',
            'build_time'  => time(),
        ];
    }

    private function initDataFiles(): void
    {
        $directory = new \RecursiveDirectoryIterator($this->dir);
        $directory->setFlags(\RecursiveDirectoryIterator::SKIP_DOTS);
        $iterator = new \RecursiveIteratorIterator($directory);

        foreach ($iterator as $file) {
            // Skip dot files and unsupported extensions
            if (!str_starts_with((string)$file->getFilename(), '.') && $this->isSupportedDataFile($file)) {
                $this->mergeDataFile($file);
            }
        }
    }

    private function isSupportedDataFile(\SplFileInfo $file): bool
    {
        return in_array(strtolower($file->getExtension()), ['yml', 'yaml', 'json'], true);
    }

    /**
     * @return array<string, mixed>
     */
    private function parseDataFile(\SplFileInfo $file): array
    {
        $path = $file->getPathname();
        $ext  = strtolower($file->getExtension());

        if ($ext === 'json') {
            $contents = file_get_contents($path);
            if ($contents === false) {
                throw new ConfigException("Failed to read data file '$path'");
            }
            $data = json_decode($contents, true);
            if (!is_array($data)) {
                throw new ConfigException("Failed to parse JSON data file '$path': " . json_last_error_msg());
            }

            return $data;
        }

        try {
            $data = Yaml::parseFile($path);
        } catch (\Symfony\Component\Yaml\Exception\ParseException $e) {
            throw new ConfigException("Failed to parse data file '$path': " . $e->getMessage(), 0, $e);
        }

        return is_array($data) ? $data : [];
    }

    private function mergeDataFile(\SplFileInfo $file): void
    {
        $fileData = $this->parseDataFile($file);
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
        $dirLength = strlen($this->dir) + 1; // The length of the data folder name + 1
        $dataPath  = substr_replace($file->getPathname(), '', 0, $dirLength);

        // Remove the extension
        $extLength = strlen($file->getExtension()) + 1;

        return substr_replace($dataPath, '', $extLength * -1, $extLength);
    }

    /**
     * @param array<string, mixed> $pageData
     *
     * @return array<string, mixed>
     */
    public function generatePageData(array $pageData): array
    {
        return [
            'data'   => $this->data,
            'proton' => $this->env,
            'page'   => $pageData,
        ];
    }
}
