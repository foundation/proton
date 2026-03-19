<?php

use App\Proton\Config;
use App\Proton\FilesystemManager;
use Tests\Helpers\TestFixtures;

uses(TestFixtures::class);

beforeEach(function () {
    $this->setUpTempProject();
});

afterEach(function () {
    $this->tearDownTempProject();
});

test('getAllFiles returns files from directory', function () {
    $this->createPage('index.html', '<h1>Home</h1>');
    $this->createPage('about.html', '<h1>About</h1>');

    $config = new Config();
    $fsManager = new FilesystemManager($config);
    $files = $fsManager->getAllFiles('src/pages');

    expect($files)->toContain('index.html');
    expect($files)->toContain('about.html');
    expect(count($files))->toBe(2);
});

test('getAllFiles returns nested files with relative paths', function () {
    $this->createPage('blog/post1.html', '<h1>Post 1</h1>');
    $this->createPage('blog/post2.html', '<h1>Post 2</h1>');

    $config = new Config();
    $fsManager = new FilesystemManager($config);
    $files = $fsManager->getAllFiles('src/pages');

    expect($files)->toContain('blog/post1.html');
    expect($files)->toContain('blog/post2.html');
});

test('getAllFiles skips dot files', function () {
    $this->createPage('index.html', '<h1>Home</h1>');
    $this->createPage('.hidden', 'secret');

    $config = new Config();
    $fsManager = new FilesystemManager($config);
    $files = $fsManager->getAllFiles('src/pages');

    expect($files)->toContain('index.html');
    expect($files)->not->toContain('.hidden');
});

test('pathsExist returns true when all paths exist', function () {
    $config = new Config();
    $fsManager = new FilesystemManager($config);

    expect($fsManager->pathsExist())->toBeTrue();
});

test('pathsExist returns false when a path is missing', function () {
    rmdir($this->tempDir . '/src/macros');

    $config = new Config();
    $fsManager = new FilesystemManager($config);

    expect($fsManager->pathsExist())->toBeFalse();
});

test('pathsExist ignores dist directory', function () {
    rmdir($this->tempDir . '/dist');

    $config = new Config();
    $fsManager = new FilesystemManager($config);

    expect($fsManager->pathsExist())->toBeTrue();
});

test('pathChecker throws when paths missing', function () {
    rmdir($this->tempDir . '/src/macros');

    $config = new Config();
    $fsManager = new FilesystemManager($config);

    expect(fn () => $fsManager->pathChecker())->toThrow(\Exception::class);
});

test('initPaths creates missing directories', function () {
    rmdir($this->tempDir . '/src/macros');

    $config = new Config();
    $fsManager = new FilesystemManager($config);
    $fsManager->initPaths();

    expect(is_dir($this->tempDir . '/src/macros'))->toBeTrue();
});

test('cleanupDist removes dist directory', function () {
    // Put something in dist
    file_put_contents($this->tempDir . '/dist/test.html', '<html></html>');

    $config = new Config();
    $fsManager = new FilesystemManager($config);
    $fsManager->cleanupDist();

    expect(is_dir($this->tempDir . '/dist'))->toBeFalse();
});

test('rm_rf removes directory recursively', function () {
    $dir = $this->tempDir . '/test_rm';
    mkdir($dir . '/sub', 0777, true);
    file_put_contents($dir . '/sub/file.txt', 'test');

    FilesystemManager::rm_rf($dir);

    expect(is_dir($dir))->toBeFalse();
});

test('rm_rf handles non-existent directory', function () {
    FilesystemManager::rm_rf($this->tempDir . '/nonexistent');

    // Should not throw
    expect(true)->toBeTrue();
});
