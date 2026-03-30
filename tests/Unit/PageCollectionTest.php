<?php

use App\Proton\Config;
use App\Proton\PageCollection;
use Tests\Helpers\TestFixtures;

uses(TestFixtures::class);

beforeEach(function (): void {
    $this->setUpTempProject();
});

afterEach(function (): void {
    $this->tearDownTempProject();
});

test('collects page metadata from frontmatter', function (): void {
    $this->createPage('docs/install.md', '# Install', ['title' => 'Installation']);

    $config = new Config();
    $pages  = PageCollection::collect(['docs/install.md'], $config);

    expect($pages)->toHaveCount(1);
    expect($pages[0]['title'])->toBe('Installation');
    expect($pages[0]['filename'])->toBe('install');
    expect($pages[0]['dirname'])->toBe('docs');
    expect($pages[0]['ext'])->toBe('md');
});

test('generates clean URL with autoindex', function (): void {
    $this->createPage('docs/install.md', '# Install', ['title' => 'Installation']);

    $config = new Config();
    $pages  = PageCollection::collect(['docs/install.md'], $config);

    expect($pages[0]['url'])->toBe('/docs/install/');
});

test('generates clean URL for index pages', function (): void {
    $this->createPage('docs/index.html', '<h1>Docs</h1>');

    $config = new Config();
    $pages  = PageCollection::collect(['docs/index.html'], $config);

    expect($pages[0]['url'])->toBe('/docs/');
});

test('generates root URL for root index', function (): void {
    $this->createPage('index.html', '<h1>Home</h1>');

    $config = new Config();
    $pages  = PageCollection::collect(['index.html'], $config);

    expect($pages[0]['url'])->toBe('/');
});

test('falls back to filename for title when not in frontmatter', function (): void {
    $this->createPage('getting-started.md', '# Hello');

    $config = new Config();
    $pages  = PageCollection::collect(['getting-started.md'], $config);

    expect($pages[0]['title'])->toBe('Getting started');
});

test('includes custom frontmatter fields', function (): void {
    $this->createPage('docs/config.md', '# Config', [
        'title'     => 'Configuration',
        'nav_group' => 'Core Concepts',
        'nav_order' => 1,
    ]);

    $config = new Config();
    $pages  = PageCollection::collect(['docs/config.md'], $config);

    expect($pages[0]['nav_group'])->toBe('Core Concepts');
    expect($pages[0]['nav_order'])->toBe(1);
});

test('uses custom output path from frontmatter', function (): void {
    $this->createPage('feed.html', '<rss></rss>', ['output' => 'feed.xml']);

    $config = new Config();
    $pages  = PageCollection::collect(['feed.html'], $config);

    expect($pages[0]['url'])->toBe('/feed.xml');
});

test('skips non-existent files', function (): void {
    $config = new Config();
    $pages  = PageCollection::collect(['nonexistent.html'], $config);

    expect($pages)->toBeEmpty();
});

test('collects multiple pages', function (): void {
    $this->createPage('docs/install.md', '# Install', ['title' => 'Installation']);
    $this->createPage('docs/config.md', '# Config', ['title' => 'Configuration']);
    $this->createPage('index.html', '<h1>Home</h1>');

    $config = new Config();
    $pages  = PageCollection::collect(['docs/install.md', 'docs/config.md', 'index.html'], $config);

    expect($pages)->toHaveCount(3);
});

test('dirname is null for root-level pages', function (): void {
    $this->createPage('about.md', '# About');

    $config = new Config();
    $pages  = PageCollection::collect(['about.md'], $config);

    expect($pages[0]['dirname'])->toBeNull();
});

test('generates flat URL when autoindex disabled', function (): void {
    $this->createConfigFile(['autoindex' => false]);
    $this->createPage('about.md', '# About');

    $config = new Config();
    $pages  = PageCollection::collect(['about.md'], $config);

    expect($pages[0]['url'])->toBe('/about.html');
});
