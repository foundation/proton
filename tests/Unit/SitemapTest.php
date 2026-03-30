<?php

use App\Proton\Config;
use App\Proton\FilesystemManager;
use App\Proton\Sitemap;
use Tests\Helpers\TestFixtures;

uses(TestFixtures::class);

beforeEach(function (): void {
    $this->setUpTempProject();
});

afterEach(function (): void {
    $this->tearDownTempProject();
});

test('generates sitemap xml', function (): void {
    // Create some files in dist
    file_put_contents($this->tempDir . '/dist/index.html', '<html></html>');
    mkdir($this->tempDir . '/dist/about', 0777, true);
    file_put_contents($this->tempDir . '/dist/about/index.html', '<html></html>');

    $config  = new Config();
    $sitemap = new Sitemap($config, new FilesystemManager($config));
    $sitemap->write();

    expect(file_exists($this->tempDir . '/dist/sitemap.xml'))->toBeTrue();
    $content = file_get_contents($this->tempDir . '/dist/sitemap.xml');
    // index.html should be stripped to clean URLs
    expect($content)->not->toContain('index.html');
    expect($content)->toContain('example.com/');
    expect($content)->toContain('example.com/about/');
});

test('sitemap only includes html and php files', function (): void {
    file_put_contents($this->tempDir . '/dist/index.html', '<html></html>');
    file_put_contents($this->tempDir . '/dist/style.css', 'body{}');
    file_put_contents($this->tempDir . '/dist/app.js', 'var x;');
    mkdir($this->tempDir . '/dist/api', 0777, true);
    file_put_contents($this->tempDir . '/dist/api/data.php', '<?php');

    $config  = new Config();
    $sitemap = new Sitemap($config, new FilesystemManager($config));
    $sitemap->write();

    $content = file_get_contents($this->tempDir . '/dist/sitemap.xml');
    expect($content)->toContain('example.com/');
    expect($content)->toContain('api/data.php');
    expect($content)->not->toContain('style.css');
    expect($content)->not->toContain('app.js');
});

test('sitemap strips index.php from URLs', function (): void {
    mkdir($this->tempDir . '/dist/contact', 0777, true);
    file_put_contents($this->tempDir . '/dist/contact/index.php', '<?php');

    $config  = new Config();
    $sitemap = new Sitemap($config, new FilesystemManager($config));
    $sitemap->write();

    $content = file_get_contents($this->tempDir . '/dist/sitemap.xml');
    expect($content)->not->toContain('index.php');
    expect($content)->toContain('contact/');
});

test('sitemap uses configured domain', function (): void {
    $this->createConfigFile(['domain' => 'https://mysite.com']);
    file_put_contents($this->tempDir . '/dist/index.html', '<html></html>');

    $config  = new Config();
    $sitemap = new Sitemap($config, new FilesystemManager($config));
    $sitemap->write();

    $content = file_get_contents($this->tempDir . '/dist/sitemap.xml');
    expect($content)->toContain('https://mysite.com');
});
