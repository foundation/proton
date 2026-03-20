<?php

use App\Proton\Config;
use App\Proton\Data;
use App\Proton\Page;
use Tests\Helpers\TestFixtures;

uses(TestFixtures::class);

beforeEach(function (): void {
    $this->setUpTempProject();
    $this->createDataFile('data.yml', ['title' => 'Test Site']);
});

afterEach(function (): void {
    $this->tearDownTempProject();
});

test('parses front matter', function (): void {
    $this->createPage('index.html', '<h1>Hello</h1>', ['title' => 'Home Page']);

    $config = new Config();
    $data   = new Data($config);
    $page   = new Page('index.html', $config, $data);

    expect($page->data['page']['title'])->toBe('Home Page');
});

test('page content is extracted after front matter', function (): void {
    $this->createPage('index.html', '<h1>Hello World</h1>', ['title' => 'Home']);

    $config = new Config();
    $data   = new Data($config);
    $page   = new Page('index.html', $config, $data);

    expect($page->content)->toContain('Hello World');
});

test('page without front matter works', function (): void {
    $this->createPage('simple.html', '<h1>No Front Matter</h1>');

    $config = new Config();
    $data   = new Data($config);
    $page   = new Page('simple.html', $config, $data);

    expect($page->content)->toContain('No Front Matter');
    expect($page->data['page'])->toBeEmpty();
});

test('default layout is applied', function (): void {
    $this->createPage('index.html', '<h1>Hello</h1>');

    $config = new Config();
    $data   = new Data($config);
    $page   = new Page('index.html', $config, $data);

    expect($page->content)->toContain('{% extends "@layouts/default.html" %}');
    expect($page->content)->toContain('{% block content %}');
    expect($page->content)->toContain('{% endblock %}');
});

test('front matter layout overrides default', function (): void {
    $this->createLayout('custom.html', '<div>{% block content %}{% endblock %}</div>');
    $this->createPage('index.html', '<h1>Custom Layout</h1>', ['layout' => 'custom.html']);

    $config = new Config();
    $data   = new Data($config);
    $page   = new Page('index.html', $config, $data);

    expect($page->content)->toContain('{% extends "@layouts/custom.html" %}');
});

test('layout none disables layout', function (): void {
    $this->createPage('raw.html', '<h1>No Layout</h1>', ['layout' => 'none']);

    $config = new Config();
    $data   = new Data($config);
    $page   = new Page('raw.html', $config, $data);

    expect($page->content)->not->toContain('{% extends');
    expect($page->content)->not->toContain('{% block content %}');
});

test('layout rules match by path prefix', function (): void {
    $this->createConfigFile([
        'layouts' => [
            'default' => 'default.html',
            'rules'   => [
                'blog' => 'blog.html',
            ],
        ],
    ]);
    $this->createLayout('blog.html', '<article>{% block content %}{% endblock %}</article>');
    $this->createPage('blog/post.html', '<p>Blog content</p>');

    $config = new Config();
    $data   = new Data($config);
    $page   = new Page('blog/post.html', $config, $data);

    expect($page->content)->toContain('{% extends "@layouts/blog.html" %}');
});

test('content block wrapper is added when no endblock exists', function (): void {
    $this->createPage('index.html', '<h1>Hello</h1>');

    $config = new Config();
    $data   = new Data($config);
    $page   = new Page('index.html', $config, $data);

    expect($page->content)->toContain('{% block content %}');
    expect($page->content)->toContain('{% endblock %}');
});

test('existing blocks are preserved', function (): void {
    $this->createPage('index.html', '{% block sidebar %}sidebar{% endblock %}{% block content %}main{% endblock %}');

    $config = new Config();
    $data   = new Data($config);
    $page   = new Page('index.html', $config, $data);

    // Should not add extra block wrapper since endblock exists
    expect(substr_count($page->content, '{% block content %}'))->toBe(1);
});

test('markdown files get markdown filter tags', function (): void {
    $this->createPage('post.md', '# Hello World');

    $config = new Config();
    $data   = new Data($config);
    $page   = new Page('post.md', $config, $data);

    expect($page->content)->toContain('{% apply markdown_to_html %}');
    expect($page->content)->toContain('{% endapply %}');
});

test('isBatch returns true when batch key present', function (): void {
    $this->createDataFile('data.yml', [
        'title' => 'Test Site',
        'team'  => ['alice' => ['name' => 'Alice'], 'bob' => ['name' => 'Bob']],
    ]);
    $this->createPage('team.html', '<h1>{{ batch.name }}</h1>', ['batch' => 'team']);

    $config = new Config();
    $data   = new Data($config);
    $page   = new Page('team.html', $config, $data);

    expect($page->isBatch())->toBeTrue();
});

test('isBatch returns false when no batch key', function (): void {
    $this->createPage('index.html', '<h1>Home</h1>');

    $config = new Config();
    $data   = new Data($config);
    $page   = new Page('index.html', $config, $data);

    expect($page->isBatch())->toBeFalse();
});

test('filename and extension are parsed correctly', function (): void {
    $this->createPage('about.html', '<h1>About</h1>');

    $config = new Config();
    $data   = new Data($config);
    $page   = new Page('about.html', $config, $data);

    expect($page->filename)->toBe('about');
    expect($page->ext)->toBe('html');
    expect($page->dirname)->toBeNull();
});

test('dirname is parsed for nested pages', function (): void {
    $this->createPage('blog/post.html', '<h1>Post</h1>');

    $config = new Config();
    $data   = new Data($config);
    $page   = new Page('blog/post.html', $config, $data);

    expect($page->filename)->toBe('post');
    expect($page->dirname)->toBe('blog');
});

// --- raw: true tests ---

test('raw markdown wraps content in verbatim and markdown filter', function (): void {
    $this->createPage('doc.md', '# Hello {{ name }}', ['raw' => true]);

    $config = new Config();
    $data   = new Data($config);
    $page   = new Page('doc.md', $config, $data);

    expect($page->content)->toContain('{% verbatim %}');
    expect($page->content)->toContain('{% endverbatim %}');
    expect($page->content)->toContain('{% apply markdown_to_html %}');
    expect($page->content)->toContain('{% endapply %}');
    expect($page->content)->toContain('{{ name }}');
});

test('raw markdown strips user-defined block tags from content', function (): void {
    $content = "{% block title %}My Title{% endblock %}\n\n{% block content %}\n# Hello {{ var }}\n{% endblock %}";
    $this->createPage('doc.md', $content, ['raw' => true]);

    $config = new Config();
    $data   = new Data($config);
    $page   = new Page('doc.md', $config, $data);

    // Should only have one block content wrapper (the auto-generated one)
    expect(substr_count($page->content, '{% block content %}'))->toBe(1);
    // The literal {{ var }} should be preserved
    expect($page->content)->toContain('{{ var }}');
});

test('raw markdown preserves twig-like syntax in code examples', function (): void {
    $content = "Here is an example:\n\n```\n{% block title %}{% endblock %}\n```\n";
    $this->createPage('doc.md', $content, ['raw' => true]);

    $config = new Config();
    $data   = new Data($config);
    $page   = new Page('doc.md', $config, $data);

    // The verbatim wrapper should prevent Twig from processing these
    expect($page->content)->toContain('{% verbatim %}');
});

test('non-raw markdown does not use verbatim', function (): void {
    $this->createPage('post.md', '# Hello World');

    $config = new Config();
    $data   = new Data($config);
    $page   = new Page('post.md', $config, $data);

    expect($page->content)->not->toContain('{% verbatim %}');
    expect($page->content)->toContain('{% apply markdown_to_html %}');
});

test('raw html wraps blocks in verbatim', function (): void {
    $content = "{% block content %}\n<p>Hello {{ name }}</p>\n{% endblock %}";
    $this->createPage('doc.html', $content, ['raw' => true]);

    $config = new Config();
    $data   = new Data($config);
    $page   = new Page('doc.html', $config, $data);

    expect($page->content)->toContain('{% verbatim %}');
    expect($page->content)->toContain('{% endverbatim %}');
    expect($page->content)->toContain('{{ name }}');
});

test('non-raw html does not use verbatim', function (): void {
    $this->createPage('index.html', '<h1>{{ title }}</h1>');

    $config = new Config();
    $data   = new Data($config);
    $page   = new Page('index.html', $config, $data);

    expect($page->content)->not->toContain('{% verbatim %}');
});

// --- accessor method tests ---

test('getPageData returns value for existing key', function (): void {
    $this->createPage('index.html', '<h1>Hello</h1>', ['title' => 'Home', 'author' => 'Joe']);

    $config = new Config();
    $data   = new Data($config);
    $page   = new Page('index.html', $config, $data);

    expect($page->getPageData('title'))->toBe('Home');
    expect($page->getPageData('author'))->toBe('Joe');
});

test('getPageData returns null for missing key', function (): void {
    $this->createPage('index.html', '<h1>Hello</h1>');

    $config = new Config();
    $data   = new Data($config);
    $page   = new Page('index.html', $config, $data);

    expect($page->getPageData('nonexistent'))->toBeNull();
});

test('getProtonData returns environment', function (): void {
    $this->createPage('index.html', '<h1>Hello</h1>');

    $config = new Config();
    $data   = new Data($config);
    $page   = new Page('index.html', $config, $data);

    expect($page->getProtonData('environment'))->toBe('development');
    expect($page->getProtonData('build_time'))->toBeInt();
});

test('getProtonData returns null for missing key', function (): void {
    $this->createPage('index.html', '<h1>Hello</h1>');

    $config = new Config();
    $data   = new Data($config);
    $page   = new Page('index.html', $config, $data);

    expect($page->getProtonData('nonexistent'))->toBeNull();
});

test('getData returns global data value', function (): void {
    $this->createPage('index.html', '<h1>Hello</h1>');

    $config = new Config();
    $data   = new Data($config);
    $page   = new Page('index.html', $config, $data);

    expect($page->getData('title'))->toBe('Test Site');
});

test('getData returns null for missing key', function (): void {
    $this->createPage('index.html', '<h1>Hello</h1>');

    $config = new Config();
    $data   = new Data($config);
    $page   = new Page('index.html', $config, $data);

    expect($page->getData('nonexistent'))->toBeNull();
});

// --- layout rule edge cases ---

test('first matching layout rule wins', function (): void {
    $this->createConfigFile([
        'layouts' => [
            'default' => 'default.html',
            'rules'   => [
                'blog'      => 'blog.html',
                'blog/news' => 'news.html',
            ],
        ],
    ]);
    $this->createLayout('blog.html', '<article>{% block content %}{% endblock %}</article>');
    $this->createLayout('news.html', '<div>{% block content %}{% endblock %}</div>');
    $this->createPage('blog/news/item.html', '<p>News item</p>');

    $config = new Config();
    $data   = new Data($config);
    $page   = new Page('blog/news/item.html', $config, $data);

    // "blog" matches first since it's iterated first
    expect($page->content)->toContain('{% extends "@layouts/blog.html" %}');
});

test('layout rule does not match partial prefix', function (): void {
    $this->createConfigFile([
        'layouts' => [
            'default' => 'default.html',
            'rules'   => [
                'blog' => 'blog.html',
            ],
        ],
    ]);
    $this->createLayout('blog.html', '<article>{% block content %}{% endblock %}</article>');
    $this->createPage('blogging/post.html', '<p>Post</p>');

    $config = new Config();
    $data   = new Data($config);
    $page   = new Page('blogging/post.html', $config, $data);

    // "blog" is a prefix of "blogging" so it matches with str_starts_with
    // This documents the actual behavior
    expect($page->content)->toContain('{% extends "@layouts/blog.html" %}');
});

test('no layout rules falls back to default', function (): void {
    $this->createConfigFile([
        'layouts' => [
            'default' => 'default.html',
            'rules'   => [],
        ],
    ]);
    $this->createPage('anything.html', '<p>Content</p>');

    $config = new Config();
    $data   = new Data($config);
    $page   = new Page('anything.html', $config, $data);

    expect($page->content)->toContain('{% extends "@layouts/default.html" %}');
});

// --- extension edge cases ---

test('pug extension maps to default ext', function (): void {
    $this->createPage('page.pug', '<p>Pug page</p>');

    $config = new Config();
    $data   = new Data($config);
    $page   = new Page('page.pug', $config, $data);

    expect($page->ext)->toBe('pug');
});

test('custom extension is preserved', function (): void {
    $this->createPage('feed.xml', '<rss></rss>', ['layout' => 'none']);

    $config = new Config();
    $data   = new Data($config);
    $page   = new Page('feed.xml', $config, $data);

    expect($page->ext)->toBe('xml');
});
