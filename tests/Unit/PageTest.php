<?php

use App\Proton\Config;
use App\Proton\Data;
use App\Proton\Page;
use Tests\Helpers\TestFixtures;

uses(TestFixtures::class);

beforeEach(function () {
    $this->setUpTempProject();
    $this->createDataFile('data.yml', ['title' => 'Test Site']);
});

afterEach(function () {
    $this->tearDownTempProject();
});

test('parses front matter', function () {
    $this->createPage('index.html', '<h1>Hello</h1>', ['title' => 'Home Page']);

    $config = new Config();
    $data = new Data($config);
    $page = new Page('index.html', $config, $data);

    expect($page->data['page']['title'])->toBe('Home Page');
});

test('page content is extracted after front matter', function () {
    $this->createPage('index.html', '<h1>Hello World</h1>', ['title' => 'Home']);

    $config = new Config();
    $data = new Data($config);
    $page = new Page('index.html', $config, $data);

    expect($page->content)->toContain('Hello World');
});

test('page without front matter works', function () {
    $this->createPage('simple.html', '<h1>No Front Matter</h1>');

    $config = new Config();
    $data = new Data($config);
    $page = new Page('simple.html', $config, $data);

    expect($page->content)->toContain('No Front Matter');
    expect($page->data['page'])->toBeEmpty();
});

test('default layout is applied', function () {
    $this->createPage('index.html', '<h1>Hello</h1>');

    $config = new Config();
    $data = new Data($config);
    $page = new Page('index.html', $config, $data);

    expect($page->content)->toContain('{% extends "@layouts/default.html" %}');
    expect($page->content)->toContain('{% block content %}');
    expect($page->content)->toContain('{% endblock %}');
});

test('front matter layout overrides default', function () {
    $this->createLayout('custom.html', '<div>{% block content %}{% endblock %}</div>');
    $this->createPage('index.html', '<h1>Custom Layout</h1>', ['layout' => 'custom.html']);

    $config = new Config();
    $data = new Data($config);
    $page = new Page('index.html', $config, $data);

    expect($page->content)->toContain('{% extends "@layouts/custom.html" %}');
});

test('layout none disables layout', function () {
    $this->createPage('raw.html', '<h1>No Layout</h1>', ['layout' => 'none']);

    $config = new Config();
    $data = new Data($config);
    $page = new Page('raw.html', $config, $data);

    expect($page->content)->not->toContain('{% extends');
    expect($page->content)->not->toContain('{% block content %}');
});

test('layout rules match by path prefix', function () {
    $this->createConfigFile([
        'layouts' => [
            'default' => 'default.html',
            'rules' => [
                'blog' => 'blog.html',
            ],
        ],
    ]);
    $this->createLayout('blog.html', '<article>{% block content %}{% endblock %}</article>');
    $this->createPage('blog/post.html', '<p>Blog content</p>');

    $config = new Config();
    $data = new Data($config);
    $page = new Page('blog/post.html', $config, $data);

    expect($page->content)->toContain('{% extends "@layouts/blog.html" %}');
});

test('content block wrapper is added when no endblock exists', function () {
    $this->createPage('index.html', '<h1>Hello</h1>');

    $config = new Config();
    $data = new Data($config);
    $page = new Page('index.html', $config, $data);

    expect($page->content)->toContain('{% block content %}');
    expect($page->content)->toContain('{% endblock %}');
});

test('existing blocks are preserved', function () {
    $this->createPage('index.html', '{% block sidebar %}sidebar{% endblock %}{% block content %}main{% endblock %}');

    $config = new Config();
    $data = new Data($config);
    $page = new Page('index.html', $config, $data);

    // Should not add extra block wrapper since endblock exists
    expect(substr_count($page->content, '{% block content %}'))->toBe(1);
});

test('markdown files get markdown filter tags', function () {
    $this->createPage('post.md', '# Hello World');

    $config = new Config();
    $data = new Data($config);
    $page = new Page('post.md', $config, $data);

    expect($page->content)->toContain('{% apply markdown_to_html %}');
    expect($page->content)->toContain('{% endapply %}');
});

test('isBatch returns true when batch key present', function () {
    $this->createDataFile('data.yml', [
        'title' => 'Test Site',
        'team' => ['alice' => ['name' => 'Alice'], 'bob' => ['name' => 'Bob']],
    ]);
    $this->createPage('team.html', '<h1>{{ batch.name }}</h1>', ['batch' => 'team']);

    $config = new Config();
    $data = new Data($config);
    $page = new Page('team.html', $config, $data);

    expect($page->isBatch())->toBeTrue();
});

test('isBatch returns false when no batch key', function () {
    $this->createPage('index.html', '<h1>Home</h1>');

    $config = new Config();
    $data = new Data($config);
    $page = new Page('index.html', $config, $data);

    expect($page->isBatch())->toBeFalse();
});

test('filename and extension are parsed correctly', function () {
    $this->createPage('about.html', '<h1>About</h1>');

    $config = new Config();
    $data = new Data($config);
    $page = new Page('about.html', $config, $data);

    expect($page->filename)->toBe('about');
    expect($page->ext)->toBe('html');
    expect($page->dirname)->toBeNull();
});

test('dirname is parsed for nested pages', function () {
    $this->createPage('blog/post.html', '<h1>Post</h1>');

    $config = new Config();
    $data = new Data($config);
    $page = new Page('blog/post.html', $config, $data);

    expect($page->filename)->toBe('post');
    expect($page->dirname)->toBe('blog');
});
