<?php

use App\Proton\Config;
use App\Proton\Page;
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
    expect($pages[0]['canonical'])->toBe($config->settings->domain . '/docs/install/');
    expect($pages[0]['path'])->toBe('docs/install.md');
    expect($pages[0]['outputPath'])->toBe('docs/install/index.html');
    expect($pages[0]['depth'])->toBe(1);
    expect($pages[0]['isIndex'])->toBeFalse();
    expect($pages[0]['parent'])->toBe('/docs/');
    expect($pages[0]['slug'])->toBe('install');
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

test('template extensions revert to default ext in URL', function (): void {
    $this->createPage('page.twig', '<p>Twig</p>');
    $this->createPage('doc.pug', '<p>Pug</p>');

    $config = new Config();
    $pages  = PageCollection::collect(['page.twig', 'doc.pug'], $config);

    // Both twig and pug should produce clean URLs (autoindex on by default)
    expect($pages[0]['url'])->toBe('/page/');
    expect($pages[1]['url'])->toBe('/doc/');
});

test('custom file extension is preserved in URL', function (): void {
    $this->createConfigFile(['autoindex' => false]);
    $this->createPage('robots.txt', 'User-agent: *');

    $config = new Config();
    $pages  = PageCollection::collect(['robots.txt'], $config);

    expect($pages[0]['url'])->toBe('/robots.txt');
});

test('page with empty front matter collects correctly', function (): void {
    $path = $this->tempDir . '/src/pages/empty-fm.html';
    file_put_contents($path, "---\n\n---\n<h1>Content</h1>");

    $config = new Config();
    $pages  = PageCollection::collect(['empty-fm.html'], $config);

    expect($pages)->toHaveCount(1);
    expect($pages[0]['filename'])->toBe('empty-fm');
});

test('empty page list returns empty array', function (): void {
    $config = new Config();
    $pages  = PageCollection::collect([], $config);

    expect($pages)->toBeEmpty();
});

test('draft pages are collected normally by PageCollection', function (): void {
    $this->createPage('draft.html', '<h1>Draft</h1>', ['draft' => true, 'title' => 'My Draft']);

    $config = new Config();
    $pages  = PageCollection::collect(['draft.html'], $config);

    expect($pages)->toHaveCount(1);
    expect($pages[0]['draft'])->toBeTrue();
    expect($pages[0]['title'])->toBe('My Draft');
});

test('collection includes canonical URL', function (): void {
    $this->createPage('about.html', '<h1>About</h1>');

    $config = new Config();
    $pages  = PageCollection::collect(['about.html'], $config);

    expect($pages[0]['canonical'])->toBe($config->settings->domain . '/about/');
});

test('collection includes path, outputPath, depth, isIndex, parent, slug', function (): void {
    $this->createPage('blog/post.html', '<p>Post</p>', ['title' => 'My Post']);

    $config = new Config();
    $pages  = PageCollection::collect(['blog/post.html'], $config);

    expect($pages[0]['path'])->toBe('blog/post.html')
        ->and($pages[0]['outputPath'])->toBe('blog/post/index.html')
        ->and($pages[0]['depth'])->toBe(1)
        ->and($pages[0]['isIndex'])->toBeFalse()
        ->and($pages[0]['parent'])->toBe('/blog/')
        ->and($pages[0]['slug'])->toBe('post');
});

test('collection isIndex is true only for exact index filename', function (): void {
    $this->createPage('index.html', '<h1>Home</h1>');
    $this->createPage('reindex.html', '<h1>Reindex</h1>');

    $config = new Config();
    $pages  = PageCollection::collect(['index.html', 'reindex.html'], $config);

    expect($pages[0]['isIndex'])->toBeTrue();
    expect($pages[1]['isIndex'])->toBeFalse();
});

test('collection canonical respects frontmatter url override', function (): void {
    $this->createPage('about.html', '<h1>About</h1>', ['url' => '/custom/']);

    $config = new Config();
    $pages  = PageCollection::collect(['about.html'], $config);

    expect($pages[0]['url'])->toBe('/custom/');
    expect($pages[0]['canonical'])->toBe($config->settings->domain . '/custom/');
});

test('collection slug normalizes filename', function (): void {
    $this->createPage('my-cool_page.html', '<h1>Hi</h1>');

    $config = new Config();
    $pages  = PageCollection::collect(['my-cool_page.html'], $config);

    expect($pages[0]['slug'])->toBe('my-cool-page');
});

test('collection depth is 0 for root pages', function (): void {
    $this->createPage('about.html', '<h1>About</h1>');

    $config = new Config();
    $pages  = PageCollection::collect(['about.html'], $config);

    expect($pages[0]['depth'])->toBe(0);
    expect($pages[0]['parent'])->toBe('/');
});
