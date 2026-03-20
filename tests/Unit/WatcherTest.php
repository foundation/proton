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
