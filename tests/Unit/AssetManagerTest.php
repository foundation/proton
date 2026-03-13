<?php

use App\Proton\Config;
use App\Proton\AssetManager;
use Tests\Helpers\TestFixtures;

uses(TestFixtures::class);

beforeEach(function () {
    $this->setUpTempProject();
});

afterEach(function () {
    $this->tearDownTempProject();
});

test('copies assets to dist', function () {
    $this->createAsset('style.css', 'body { color: red; }');
    $this->createAsset('script.js', 'console.log("hi")');

    $config = new Config();
    $assetManager = new AssetManager($config);
    $assetManager->copyAssets();

    expect(file_exists($this->tempDir . '/dist/style.css'))->toBeTrue();
    expect(file_exists($this->tempDir . '/dist/script.js'))->toBeTrue();
    expect(file_get_contents($this->tempDir . '/dist/style.css'))->toBe('body { color: red; }');
});

test('preserves directory structure in dist', function () {
    $this->createAsset('css/main.css', 'body {}');
    $this->createAsset('js/app.js', 'var x = 1;');

    $config = new Config();
    $assetManager = new AssetManager($config);
    $assetManager->copyAssets();

    expect(file_exists($this->tempDir . '/dist/css/main.css'))->toBeTrue();
    expect(file_exists($this->tempDir . '/dist/js/app.js'))->toBeTrue();
});

test('creates dist subdirectories as needed', function () {
    $this->createAsset('images/photos/hero.jpg', 'fake-image-data');

    $config = new Config();
    $assetManager = new AssetManager($config);
    $assetManager->copyAssets();

    expect(file_exists($this->tempDir . '/dist/images/photos/hero.jpg'))->toBeTrue();
});
