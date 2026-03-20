<?php

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

test('getAllFiles returns files from directory', function (): void {
    $this->createPage('index.html', '<h1>Home</h1>');
    $this->createPage('about.html', '<h1>About</h1>');

    $config    = new Config();
    $fsManager = new FilesystemManager($config);
    $files     = $fsManager->getAllFiles('src/pages');

    expect($files)->toContain('index.html');
    expect($files)->toContain('about.html');
    expect(count($files))->toBe(2);
});

test('getAllFiles returns nested files with relative paths', function (): void {
    $this->createPage('blog/post1.html', '<h1>Post 1</h1>');
    $this->createPage('blog/post2.html', '<h1>Post 2</h1>');

    $config    = new Config();
    $fsManager = new FilesystemManager($config);
    $files     = $fsManager->getAllFiles('src/pages');

    expect($files)->toContain('blog/post1.html');
    expect($files)->toContain('blog/post2.html');
});

test('getAllFiles skips dot files', function (): void {
    $this->createPage('index.html', '<h1>Home</h1>');
    $this->createPage('.hidden', 'secret');

    $config    = new Config();
    $fsManager = new FilesystemManager($config);
    $files     = $fsManager->getAllFiles('src/pages');

    expect($files)->toContain('index.html');
    expect($files)->not->toContain('.hidden');
});

test('pathsExist returns true when all paths exist', function (): void {
    $config    = new Config();
    $fsManager = new FilesystemManager($config);

    expect($fsManager->pathsExist())->toBeTrue();
});

test('pathsExist returns false when a path is missing', function (): void {
    rmdir($this->tempDir . '/src/macros');

    $config    = new Config();
    $fsManager = new FilesystemManager($config);

    expect($fsManager->pathsExist())->toBeFalse();
});

test('pathsExist ignores dist directory', function (): void {
    rmdir($this->tempDir . '/dist');

    $config    = new Config();
    $fsManager = new FilesystemManager($config);

    expect($fsManager->pathsExist())->toBeTrue();
});

test('pathChecker throws when paths missing', function (): void {
    rmdir($this->tempDir . '/src/macros');

    $config    = new Config();
    $fsManager = new FilesystemManager($config);

    expect(fn (): bool => $fsManager->pathChecker())->toThrow(Exception::class);
});

test('initPaths creates missing directories', function (): void {
    rmdir($this->tempDir . '/src/macros');

    $config    = new Config();
    $fsManager = new FilesystemManager($config);
    $fsManager->initPaths();

    expect(is_dir($this->tempDir . '/src/macros'))->toBeTrue();
});

test('cleanupDist removes dist directory', function (): void {
    // Put something in dist
    file_put_contents($this->tempDir . '/dist/test.html', '<html></html>');

    $config    = new Config();
    $fsManager = new FilesystemManager($config);
    $fsManager->cleanupDist();

    expect(is_dir($this->tempDir . '/dist'))->toBeFalse();
});

test('rm_rf removes directory recursively', function (): void {
    $dir = $this->tempDir . '/test_rm';
    mkdir($dir . '/sub', 0777, true);
    file_put_contents($dir . '/sub/file.txt', 'test');

    FilesystemManager::rm_rf($dir);

    expect(is_dir($dir))->toBeFalse();
});

test('rm_rf handles non-existent directory', function (): void {
    FilesystemManager::rm_rf($this->tempDir . '/nonexistent');

    // Should not throw
    expect(true)->toBeTrue();
});

test('deleteFromDist removes file from dist', function (): void {
    file_put_contents($this->tempDir . '/dist/page.html', '<html></html>');

    $config    = new Config();
    $fsManager = new FilesystemManager($config);
    $result    = $fsManager->deleteFromDist('page.html');

    expect($result)->toBeTrue();
    expect(file_exists($this->tempDir . '/dist/page.html'))->toBeFalse();
});

test('deleteFromDist returns true for non-existent file', function (): void {
    $config    = new Config();
    $fsManager = new FilesystemManager($config);
    $result    = $fsManager->deleteFromDist('nonexistent.html');

    expect($result)->toBeTrue();
});

test('deleteFromDist handles nested paths', function (): void {
    mkdir($this->tempDir . '/dist/blog', 0777, true);
    file_put_contents($this->tempDir . '/dist/blog/post.html', '<html></html>');

    $config    = new Config();
    $fsManager = new FilesystemManager($config);
    $result    = $fsManager->deleteFromDist('blog/post.html');

    expect($result)->toBeTrue();
    expect(file_exists($this->tempDir . '/dist/blog/post.html'))->toBeFalse();
});

test('pathChecker returns true when all paths exist', function (): void {
    $config    = new Config();
    $fsManager = new FilesystemManager($config);

    expect($fsManager->pathChecker())->toBeTrue();
});

test('initPaths does not error when all paths already exist', function (): void {
    $config    = new Config();
    $fsManager = new FilesystemManager($config);
    $fsManager->initPaths();

    expect($fsManager->pathsExist())->toBeTrue();
});

test('clearCache removes proton cache directory', function (): void {
    mkdir($this->tempDir . '/.proton-cache', 0777, true);
    file_put_contents($this->tempDir . '/.proton-cache/twig.php', '<?php');

    $config    = new Config();
    $fsManager = new FilesystemManager($config);
    $fsManager->clearCache();

    expect(is_dir($this->tempDir . '/.proton-cache'))->toBeFalse();
});
