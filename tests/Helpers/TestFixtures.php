<?php

namespace Tests\Helpers;

use Symfony\Component\Yaml\Yaml;

trait TestFixtures
{
    protected string $tempDir;
    protected string $originalDir;

    protected function setUpTempProject(array $configOverrides = []): void
    {
        $this->originalDir = getcwd();
        $this->tempDir     = sys_get_temp_dir() . '/proton_test_' . uniqid();
        mkdir($this->tempDir, 0777, true);

        // Create default Proton directory structure
        $dirs = ['src/pages', 'src/layouts', 'src/partials', 'src/macros', 'src/data', 'src/assets', 'dist'];
        foreach ($dirs as $dir) {
            mkdir($this->tempDir . '/' . $dir, 0777, true);
        }

        // Create default layout
        $this->createLayout('default.html', '<html><body>{% block content %}{% endblock %}</body></html>');

        // Write config if overrides given
        if ($configOverrides !== []) {
            $this->createConfigFile($configOverrides);
        }

        chdir($this->tempDir);
    }

    protected function tearDownTempProject(): void
    {
        chdir($this->originalDir);
        $this->rmDir($this->tempDir);
    }

    protected function createConfigFile(array $overrides = []): string
    {
        $yaml = Yaml::dump($overrides, 4, 4, Yaml::DUMP_OBJECT_AS_MAP);
        $path = $this->tempDir . '/proton.yml';
        file_put_contents($path, $yaml);

        return $path;
    }

    protected function createPage(string $name, string $content, array $frontMatter = []): string
    {
        $path = $this->tempDir . '/src/pages/' . $name;
        $dir  = dirname($path);
        if (!is_dir($dir)) {
            mkdir($dir, 0777, true);
        }

        $fileContent = '';
        if ($frontMatter !== []) {
            $fileContent .= "---\n" . Yaml::dump($frontMatter) . "---\n";
        }
        $fileContent .= $content;

        file_put_contents($path, $fileContent);

        return $path;
    }

    protected function createLayout(string $name, string $content): string
    {
        $path = $this->tempDir . '/src/layouts/' . $name;
        $dir  = dirname($path);
        if (!is_dir($dir)) {
            mkdir($dir, 0777, true);
        }
        file_put_contents($path, $content);

        return $path;
    }

    protected function createPartial(string $name, string $content): string
    {
        $path = $this->tempDir . '/src/partials/' . $name;
        $dir  = dirname($path);
        if (!is_dir($dir)) {
            mkdir($dir, 0777, true);
        }
        file_put_contents($path, $content);

        return $path;
    }

    protected function createDataFile(string $name, array $data): string
    {
        $path = $this->tempDir . '/src/data/' . $name;
        $dir  = dirname($path);
        if (!is_dir($dir)) {
            mkdir($dir, 0777, true);
        }
        file_put_contents($path, Yaml::dump($data));

        return $path;
    }

    protected function createJsonDataFile(string $name, array $data): string
    {
        $path = $this->tempDir . '/src/data/' . $name;
        $dir  = dirname($path);
        if (!is_dir($dir)) {
            mkdir($dir, 0777, true);
        }
        file_put_contents($path, json_encode($data, JSON_PRETTY_PRINT));

        return $path;
    }

    protected function createAsset(string $name, string $content = ''): string
    {
        $path = $this->tempDir . '/src/assets/' . $name;
        $dir  = dirname($path);
        if (!is_dir($dir)) {
            mkdir($dir, 0777, true);
        }
        file_put_contents($path, $content);

        return $path;
    }

    private function rmDir(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($dir, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        );
        foreach ($iterator as $file) {
            if ($file->isDir()) {
                rmdir($file->getPathname());
            } else {
                unlink($file->getPathname());
            }
        }
        rmdir($dir);
    }
}
