<?php

use App\Proton\AssetManager;
use App\Proton\Builder;
use App\Proton\Config;
use App\Proton\Data;
use App\Proton\FilesystemManager;
use App\Proton\NullOutput;
use App\Proton\PageManager;
use Tests\Helpers\TestFixtures;

uses(TestFixtures::class);

beforeEach(function (): void {
    $this->setUpTempProject(['npmBuild' => '']);

    $this->output       = new NullOutput();
    $this->config       = new Config();
    $this->fsManager    = new FilesystemManager($this->config);
    $this->data         = new Data($this->config);
    $this->pageManager  = new PageManager($this->config, $this->data, $this->fsManager);
    $this->assetManager = new AssetManager($this->config, $this->fsManager);
});

afterEach(function (): void {
    $this->tearDownTempProject();
});

test('build compiles pages to dist', function (): void {
    $this->createPage('index.html', '<h1>Hello</h1>');

    $builder = new Builder($this->output, $this->config, $this->data, $this->fsManager, $this->pageManager, $this->assetManager);
    $builder->build();

    expect(file_exists($this->tempDir . '/dist/index.html'))->toBeTrue();
    expect(file_get_contents($this->tempDir . '/dist/index.html'))->toContain('Hello');
});

test('build copies assets to dist', function (): void {
    $this->createPage('index.html', '<h1>Hello</h1>');
    $this->createAsset('style.css', 'body { color: red; }');

    $builder = new Builder($this->output, $this->config, $this->data, $this->fsManager, $this->pageManager, $this->assetManager);
    $builder->build();

    expect(file_exists($this->tempDir . '/dist/style.css'))->toBeTrue();
    expect(file_get_contents($this->tempDir . '/dist/style.css'))->toBe('body { color: red; }');
});

test('build generates sitemap when enabled', function (): void {
    $this->createPage('index.html', '<h1>Hello</h1>');

    $builder = new Builder($this->output, $this->config, $this->data, $this->fsManager, $this->pageManager, $this->assetManager);
    $builder->build();

    expect(file_exists($this->tempDir . '/dist/sitemap.xml'))->toBeTrue();
});

test('build skips sitemap when disabled', function (): void {
    $this->createConfigFile(['sitemap' => false, 'npmBuild' => '']);
    $config       = new Config();
    $data         = new Data($config);
    $fsManager    = new FilesystemManager($config);
    $pageManager  = new PageManager($config, $data, $fsManager);
    $assetManager = new AssetManager($config, $fsManager);

    $this->createPage('index.html', '<h1>Hello</h1>');

    $builder = new Builder($this->output, $config, $data, $fsManager, $pageManager, $assetManager);
    $builder->build();

    expect(file_exists($this->tempDir . '/dist/sitemap.xml'))->toBeFalse();
});

test('clean with true removes dist and cache', function (): void {
    mkdir($this->tempDir . '/dist/sub', 0777, true);
    file_put_contents($this->tempDir . '/dist/sub/file.html', '<html></html>');
    mkdir($this->tempDir . '/.proton-cache', 0777, true);
    file_put_contents($this->tempDir . '/.proton-cache/tmp', 'cache');

    $builder = new Builder($this->output, $this->config, $this->data, $this->fsManager, $this->pageManager, $this->assetManager);
    $builder->clean(true);

    expect(is_dir($this->tempDir . '/dist'))->toBeFalse();
    expect(is_dir($this->tempDir . '/.proton-cache'))->toBeFalse();
});

test('clean with false only clears cache', function (): void {
    file_put_contents($this->tempDir . '/dist/keep.html', '<html></html>');
    mkdir($this->tempDir . '/.proton-cache', 0777, true);
    file_put_contents($this->tempDir . '/.proton-cache/tmp', 'cache');

    $builder = new Builder($this->output, $this->config, $this->data, $this->fsManager, $this->pageManager, $this->assetManager);
    $builder->clean(false);

    expect(file_exists($this->tempDir . '/dist/keep.html'))->toBeTrue();
    expect(is_dir($this->tempDir . '/.proton-cache'))->toBeFalse();
});

test('refreshData recompiles pages with updated data', function (): void {
    $this->createDataFile('data.yml', ['title' => 'Original']);
    $this->createPage('index.html', '{{ data.title }}');

    // Build initially
    $config       = new Config();
    $data         = new Data($config);
    $fsManager    = new FilesystemManager($config);
    $pageManager  = new PageManager($config, $data, $fsManager);
    $assetManager = new AssetManager($config, $fsManager);

    $builder = new Builder($this->output, $config, $data, $fsManager, $pageManager, $assetManager);
    $builder->build();

    expect(file_get_contents($this->tempDir . '/dist/index.html'))->toContain('Original');

    // Update data file and refresh
    $this->createDataFile('data.yml', ['title' => 'Updated']);
    $builder->refreshData();

    expect(file_get_contents($this->tempDir . '/dist/index.html'))->toContain('Updated');
});

test('build compiles multiple pages', function (): void {
    $this->createPage('index.html', '<h1>Home</h1>');
    $this->createPage('about.html', '<h1>About</h1>');

    $builder = new Builder($this->output, $this->config, $this->data, $this->fsManager, $this->pageManager, $this->assetManager);
    $builder->build();

    expect(file_exists($this->tempDir . '/dist/index.html'))->toBeTrue();
    expect(file_exists($this->tempDir . '/dist/about/index.html'))->toBeTrue();
});

test('NullOutput does not throw', function (): void {
    $output = new NullOutput();
    $output->info('test message');

    expect(true)->toBeTrue();
});
