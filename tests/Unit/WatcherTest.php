<?php

use App\Proton\AssetManager;
use App\Proton\Builder;
use App\Proton\Config;
use App\Proton\Data;
use App\Proton\DevServer;
use App\Proton\FileScanner;
use App\Proton\FilesystemManager;
use App\Proton\NullOutput;
use App\Proton\PageManager;
use App\Proton\Watcher;
use Tests\Helpers\TestFixtures;

uses(TestFixtures::class);

beforeEach(function (): void {
    $this->setUpTempProject();

    $this->output       = new NullOutput();
    $this->config       = new Config();
    $this->fsManager    = new FilesystemManager($this->config);
    $this->data         = new Data($this->config);
    $this->pageManager  = new PageManager($this->config, $this->data, $this->fsManager);
    $this->assetManager = new AssetManager($this->config, $this->fsManager);
    $this->builder      = new Builder($this->output, $this->config, $this->data, $this->fsManager, $this->pageManager, $this->assetManager);
    $this->scanner      = new FileScanner([$this->config->settings->paths->watch]);
    $this->server       = new DevServer($this->config->settings->paths->dist);
});

afterEach(function (): void {
    $this->tearDownTempProject();
});

test('isDataPath matches data directory', function (): void {
    $watcher = new Watcher($this->output, $this->config, $this->builder, $this->fsManager, $this->scanner, $this->server);

    expect($watcher->isDataPath('src/data/file.yml'))->toBeTrue();
    expect($watcher->isDataPath('src/pages/index.html'))->toBeFalse();
});

test('isAssetsPath matches assets directory', function (): void {
    $watcher = new Watcher($this->output, $this->config, $this->builder, $this->fsManager, $this->scanner, $this->server);

    expect($watcher->isAssetsPath('src/assets/style.css'))->toBeTrue();
    expect($watcher->isAssetsPath('src/pages/index.html'))->toBeFalse();
});

test('isPagesPath matches pages directory', function (): void {
    $watcher = new Watcher($this->output, $this->config, $this->builder, $this->fsManager, $this->scanner, $this->server);

    expect($watcher->isPagesPath('src/pages/index.html'))->toBeTrue();
    expect($watcher->isPagesPath('src/data/file.yml'))->toBeFalse();
});

test('isLayoutsPath matches layouts directory', function (): void {
    $watcher = new Watcher($this->output, $this->config, $this->builder, $this->fsManager, $this->scanner, $this->server);

    expect($watcher->isLayoutsPath('src/layouts/default.html'))->toBeTrue();
    expect($watcher->isLayoutsPath('src/pages/index.html'))->toBeFalse();
});

test('isPartialsPath matches partials directory', function (): void {
    $watcher = new Watcher($this->output, $this->config, $this->builder, $this->fsManager, $this->scanner, $this->server);

    expect($watcher->isPartialsPath('src/partials/header.html'))->toBeTrue();
    expect($watcher->isPartialsPath('src/pages/index.html'))->toBeFalse();
});

test('isMacrosPath matches macros directory', function (): void {
    $watcher = new Watcher($this->output, $this->config, $this->builder, $this->fsManager, $this->scanner, $this->server);

    expect($watcher->isMacrosPath('src/macros/helpers.html'))->toBeTrue();
    expect($watcher->isMacrosPath('src/pages/index.html'))->toBeFalse();
});

test('isTemplatesPath matches all template directories', function (): void {
    $watcher = new Watcher($this->output, $this->config, $this->builder, $this->fsManager, $this->scanner, $this->server);

    expect($watcher->isTemplatesPath('src/pages/index.html'))->toBeTrue();
    expect($watcher->isTemplatesPath('src/layouts/default.html'))->toBeTrue();
    expect($watcher->isTemplatesPath('src/partials/header.html'))->toBeTrue();
    expect($watcher->isTemplatesPath('src/macros/helpers.html'))->toBeTrue();
    expect($watcher->isTemplatesPath('src/data/file.yml'))->toBeTrue();
});

test('isTemplatesPath returns false for non-template paths', function (): void {
    $watcher = new Watcher($this->output, $this->config, $this->builder, $this->fsManager, $this->scanner, $this->server);

    expect($watcher->isTemplatesPath('package.json'))->toBeFalse();
    expect($watcher->isTemplatesPath('node_modules/foo.js'))->toBeFalse();
    expect($watcher->isTemplatesPath('webpack.config.js'))->toBeFalse();
});

// --- processBatch tests ---

test('processBatch triggers data refresh for data changes', function (): void {
    $this->createPage('index.html', '<h1>Home</h1>');
    $this->createDataFile('data.yml', ['title' => 'Site']);

    $builder = Mockery::mock(Builder::class);
    $builder->shouldReceive('refreshData')->once();
    $builder->shouldNotReceive('compilePages');
    $builder->shouldNotReceive('copyAssets');
    $builder->shouldNotReceive('buildSitemap');
    $builder->shouldNotReceive('runNPMBuild');

    $watcher = new Watcher($this->output, $this->config, $builder, $this->fsManager, $this->scanner, $this->server);

    $changes = [
        ['type' => FileScanner::EVENT_FILE_UPDATED, 'path' => 'src/data/data.yml'],
    ];

    $reflection = new ReflectionMethod($watcher, 'processBatch');
    $reflection->invoke($watcher, $changes);
});

test('processBatch triggers asset copy for asset changes', function (): void {
    $builder = Mockery::mock(Builder::class);
    $builder->shouldReceive('copyAssets')->once();
    $builder->shouldNotReceive('refreshData');
    $builder->shouldNotReceive('compilePages');
    $builder->shouldNotReceive('buildSitemap');
    $builder->shouldNotReceive('runNPMBuild');

    $watcher = new Watcher($this->output, $this->config, $builder, $this->fsManager, $this->scanner, $this->server);

    $changes = [
        ['type' => FileScanner::EVENT_FILE_UPDATED, 'path' => 'src/assets/style.css'],
    ];

    $reflection = new ReflectionMethod($watcher, 'processBatch');
    $reflection->invoke($watcher, $changes);
});

test('processBatch triggers page compile for template changes', function (): void {
    $builder = Mockery::mock(Builder::class);
    $builder->shouldReceive('compilePages')->once();
    $builder->shouldNotReceive('refreshData');
    $builder->shouldNotReceive('copyAssets');
    $builder->shouldNotReceive('buildSitemap');
    $builder->shouldNotReceive('runNPMBuild');

    $watcher = new Watcher($this->output, $this->config, $builder, $this->fsManager, $this->scanner, $this->server);

    $changes = [
        ['type' => FileScanner::EVENT_FILE_UPDATED, 'path' => 'src/layouts/default.html'],
    ];

    $reflection = new ReflectionMethod($watcher, 'processBatch');
    $reflection->invoke($watcher, $changes);
});

test('processBatch triggers page compile for page update', function (): void {
    $builder = Mockery::mock(Builder::class);
    $builder->shouldReceive('compilePages')->once();
    $builder->shouldNotReceive('refreshData');
    $builder->shouldNotReceive('copyAssets');
    $builder->shouldNotReceive('buildSitemap');
    $builder->shouldNotReceive('runNPMBuild');

    $watcher = new Watcher($this->output, $this->config, $builder, $this->fsManager, $this->scanner, $this->server);

    $changes = [
        ['type' => FileScanner::EVENT_FILE_UPDATED, 'path' => 'src/pages/index.html'],
    ];

    $reflection = new ReflectionMethod($watcher, 'processBatch');
    $reflection->invoke($watcher, $changes);
});

test('processBatch triggers sitemap rebuild for new page', function (): void {
    $builder = Mockery::mock(Builder::class);
    $builder->shouldReceive('compilePages')->once();
    $builder->shouldReceive('buildSitemap')->once();
    $builder->shouldNotReceive('refreshData');
    $builder->shouldNotReceive('copyAssets');
    $builder->shouldNotReceive('runNPMBuild');

    $watcher = new Watcher($this->output, $this->config, $builder, $this->fsManager, $this->scanner, $this->server);

    $changes = [
        ['type' => FileScanner::EVENT_FILE_CREATED, 'path' => 'src/pages/new-page.html'],
    ];

    $reflection = new ReflectionMethod($watcher, 'processBatch');
    $reflection->invoke($watcher, $changes);
});

test('processBatch triggers sitemap rebuild and page delete for deleted page', function (): void {
    // Create dist file so deleteFromDist works
    mkdir($this->tempDir . '/dist/pages', 0777, true);
    file_put_contents($this->tempDir . '/dist/pages/old.html', '<h1>Old</h1>');

    $builder = Mockery::mock(Builder::class);
    $builder->shouldReceive('buildSitemap')->once();
    $builder->shouldNotReceive('refreshData');
    $builder->shouldNotReceive('compilePages');
    $builder->shouldNotReceive('copyAssets');
    $builder->shouldNotReceive('runNPMBuild');

    $watcher = new Watcher($this->output, $this->config, $builder, $this->fsManager, $this->scanner, $this->server);

    $changes = [
        ['type' => FileScanner::EVENT_FILE_DELETED, 'path' => 'src/pages/old.html'],
    ];

    $reflection = new ReflectionMethod($watcher, 'processBatch');
    $reflection->invoke($watcher, $changes);
});

test('processBatch triggers npm build for unknown file changes', function (): void {
    $builder = Mockery::mock(Builder::class);
    $builder->shouldReceive('runNPMBuild')->once();
    $builder->shouldNotReceive('refreshData');
    $builder->shouldNotReceive('compilePages');
    $builder->shouldNotReceive('copyAssets');
    $builder->shouldNotReceive('buildSitemap');

    $watcher = new Watcher($this->output, $this->config, $builder, $this->fsManager, $this->scanner, $this->server);

    $changes = [
        ['type' => FileScanner::EVENT_FILE_UPDATED, 'path' => 'webpack.config.js'],
    ];

    $reflection = new ReflectionMethod($watcher, 'processBatch');
    $reflection->invoke($watcher, $changes);
});

test('processBatch deduplicates actions for multiple changes of same type', function (): void {
    $builder = Mockery::mock(Builder::class);
    $builder->shouldReceive('compilePages')->once(); // only once despite 3 template changes
    $builder->shouldNotReceive('refreshData');
    $builder->shouldNotReceive('copyAssets');
    $builder->shouldNotReceive('buildSitemap');
    $builder->shouldNotReceive('runNPMBuild');

    $watcher = new Watcher($this->output, $this->config, $builder, $this->fsManager, $this->scanner, $this->server);

    $changes = [
        ['type' => FileScanner::EVENT_FILE_UPDATED, 'path' => 'src/layouts/default.html'],
        ['type' => FileScanner::EVENT_FILE_UPDATED, 'path' => 'src/partials/header.html'],
        ['type' => FileScanner::EVENT_FILE_UPDATED, 'path' => 'src/pages/index.html'],
    ];

    $reflection = new ReflectionMethod($watcher, 'processBatch');
    $reflection->invoke($watcher, $changes);
});

test('processBatch handles mixed change types', function (): void {
    $builder = Mockery::mock(Builder::class);
    $builder->shouldReceive('refreshData')->once();
    $builder->shouldReceive('copyAssets')->once();
    $builder->shouldNotReceive('compilePages');
    $builder->shouldNotReceive('buildSitemap');
    $builder->shouldNotReceive('runNPMBuild');

    $watcher = new Watcher($this->output, $this->config, $builder, $this->fsManager, $this->scanner, $this->server);

    $changes = [
        ['type' => FileScanner::EVENT_FILE_UPDATED, 'path' => 'src/data/data.yml'],
        ['type' => FileScanner::EVENT_FILE_CREATED, 'path' => 'src/assets/new.css'],
    ];

    $reflection = new ReflectionMethod($watcher, 'processBatch');
    $reflection->invoke($watcher, $changes);
});
