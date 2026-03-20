<?php

use App\Proton\AssetManager;
use App\Proton\Config;
use App\Proton\FilesystemManager;
use Tests\Helpers\TestFixtures;

uses(TestFixtures::class);

beforeEach(function (): void {
    $this->setUpTempProject();
});

afterEach(function (): void {
    $this->tearDownTempProject();
});

test('copies assets to dist', function (): void {
    $this->createAsset('style.css', 'body { color: red; }');
    $this->createAsset('script.js', 'console.log("hi")');

    $config       = new Config();
    $assetManager = new AssetManager($config, new FilesystemManager($config));
    $assetManager->copyAssets();

    expect(file_exists($this->tempDir . '/dist/style.css'))->toBeTrue();
    expect(file_exists($this->tempDir . '/dist/script.js'))->toBeTrue();
    expect(file_get_contents($this->tempDir . '/dist/style.css'))->toBe('body { color: red; }');
});

test('preserves directory structure in dist', function (): void {
    $this->createAsset('css/main.css', 'body {}');
    $this->createAsset('js/app.js', 'var x = 1;');

    $config       = new Config();
    $assetManager = new AssetManager($config, new FilesystemManager($config));
    $assetManager->copyAssets();

    expect(file_exists($this->tempDir . '/dist/css/main.css'))->toBeTrue();
    expect(file_exists($this->tempDir . '/dist/js/app.js'))->toBeTrue();
});

test('creates dist subdirectories as needed', function (): void {
    $this->createAsset('images/photos/hero.jpg', 'fake-image-data');

    $config       = new Config();
    $assetManager = new AssetManager($config, new FilesystemManager($config));
    $assetManager->copyAssets();

    expect(file_exists($this->tempDir . '/dist/images/photos/hero.jpg'))->toBeTrue();
});
