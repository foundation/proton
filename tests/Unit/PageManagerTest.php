<?php

use App\Proton\Config;
use App\Proton\Data;
use App\Proton\FilesystemManager;
use App\Proton\PageManager;
use Tests\Helpers\TestFixtures;

uses(TestFixtures::class);

beforeEach(function (): void {
    $this->setUpTempProject();
});

afterEach(function (): void {
    $this->tearDownTempProject();
});

test('compilePages creates output for each page', function (): void {
    $this->createPage('index.html', '<h1>Home</h1>');
    $this->createPage('about.html', '<h1>About</h1>');

    $config      = new Config();
    $data        = new Data($config);
    $fsManager   = new FilesystemManager($config);
    $pageManager = new PageManager($config, $data, $fsManager);
    $pageManager->compilePages();

    expect(file_exists($this->tempDir . '/dist/index.html'))->toBeTrue();
    expect(file_exists($this->tempDir . '/dist/about/index.html'))->toBeTrue();
});

test('compilePages renders twig variables', function (): void {
    $this->createDataFile('data.yml', ['title' => 'My Site']);
    $this->createPage('index.html', '<h1>{{ data.title }}</h1>');

    $config      = new Config();
    $data        = new Data($config);
    $fsManager   = new FilesystemManager($config);
    $pageManager = new PageManager($config, $data, $fsManager);
    $pageManager->compilePages();

    $output = file_get_contents($this->tempDir . '/dist/index.html');
    expect($output)->toContain('My Site');
});

test('compilePages handles partials', function (): void {
    $this->createPartial('header.html', '<header>Site Header</header>');
    $this->createPage('index.html', '{% include "header.html" %}<h1>Home</h1>');

    $config      = new Config();
    $data        = new Data($config);
    $fsManager   = new FilesystemManager($config);
    $pageManager = new PageManager($config, $data, $fsManager);
    $pageManager->compilePages();

    $output = file_get_contents($this->tempDir . '/dist/index.html');
    expect($output)->toContain('Site Header');
    expect($output)->toContain('Home');
});

test('compilePages applies layout', function (): void {
    $this->createPage('index.html', '<h1>Content</h1>');

    $config      = new Config();
    $data        = new Data($config);
    $fsManager   = new FilesystemManager($config);
    $pageManager = new PageManager($config, $data, $fsManager);
    $pageManager->compilePages();

    $output = file_get_contents($this->tempDir . '/dist/index.html');
    expect($output)->toContain('<html>');
    expect($output)->toContain('<body>');
    expect($output)->toContain('Content');
});

test('compilePages handles nested page directories', function (): void {
    $this->createPage('blog/post.html', '<h1>Blog Post</h1>');

    $config      = new Config();
    $data        = new Data($config);
    $fsManager   = new FilesystemManager($config);
    $pageManager = new PageManager($config, $data, $fsManager);
    $pageManager->compilePages();

    expect(file_exists($this->tempDir . '/dist/blog/post/index.html'))->toBeTrue();
});

test('compilePages handles markdown pages', function (): void {
    $this->createPage('page.md', '# Hello World');

    $config      = new Config();
    $data        = new Data($config);
    $fsManager   = new FilesystemManager($config);
    $pageManager = new PageManager($config, $data, $fsManager);
    $pageManager->compilePages();

    $output = file_get_contents($this->tempDir . '/dist/page/index.html');
    expect($output)->toContain('<h1>Hello World</h1>');
});

test('compilePages handles batch pages', function (): void {
    $this->createDataFile('data.yml', [
        'team' => [
            'alice' => ['name' => 'Alice'],
            'bob'   => ['name' => 'Bob'],
        ],
    ]);
    $this->createPage('member.html', '<h1>{{ batch.name }}</h1>', [
        'layout' => 'none',
        'batch'  => 'team',
    ]);

    $config      = new Config();
    $data        = new Data($config);
    $fsManager   = new FilesystemManager($config);
    $pageManager = new PageManager($config, $data, $fsManager);
    $pageManager->compilePages();

    expect(file_exists($this->tempDir . '/dist/alice/index.html'))->toBeTrue();
    expect(file_exists($this->tempDir . '/dist/bob/index.html'))->toBeTrue();
});

test('refreshData reloads data from disk', function (): void {
    $this->createDataFile('data.yml', ['title' => 'Original']);

    $config      = new Config();
    $data        = new Data($config);
    $fsManager   = new FilesystemManager($config);
    $pageManager = new PageManager($config, $data, $fsManager);

    expect($data->data['title'])->toBe('Original');

    // Update data file
    $this->createDataFile('data.yml', ['title' => 'Updated']);
    $pageManager->refreshData();

    expect($data->data['title'])->toBe('Updated');
});

test('compilePages provides ksort filter', function (): void {
    $this->createDataFile('data.yml', [
        'items' => ['c' => 3, 'a' => 1, 'b' => 2],
    ]);
    $this->createPage('index.html', '{% for k, v in data.items|ksort %}{{ k }}{{ v }}{% endfor %}', [
        'layout' => 'none',
    ]);

    $config      = new Config();
    $data        = new Data($config);
    $fsManager   = new FilesystemManager($config);
    $pageManager = new PageManager($config, $data, $fsManager);
    $pageManager->compilePages();

    $output = file_get_contents($this->tempDir . '/dist/index.html');
    expect($output)->toBe('a1b2c3');
});

test('compilePages provides krsort filter', function (): void {
    $this->createDataFile('data.yml', [
        'items' => ['a' => 1, 'c' => 3, 'b' => 2],
    ]);
    $this->createPage('index.html', '{% for k, v in data.items|krsort %}{{ k }}{{ v }}{% endfor %}', [
        'layout' => 'none',
    ]);

    $config      = new Config();
    $data        = new Data($config);
    $fsManager   = new FilesystemManager($config);
    $pageManager = new PageManager($config, $data, $fsManager);
    $pageManager->compilePages();

    $output = file_get_contents($this->tempDir . '/dist/index.html');
    expect($output)->toBe('c3b2a1');
});

test('compilePages provides count filter', function (): void {
    $this->createDataFile('data.yml', [
        'items' => ['one', 'two', 'three'],
    ]);
    $this->createPage('index.html', '{{ data.items|count }}', [
        'layout' => 'none',
    ]);

    $config      = new Config();
    $data        = new Data($config);
    $fsManager   = new FilesystemManager($config);
    $pageManager = new PageManager($config, $data, $fsManager);
    $pageManager->compilePages();

    $output = file_get_contents($this->tempDir . '/dist/index.html');
    expect($output)->toBe('3');
});
