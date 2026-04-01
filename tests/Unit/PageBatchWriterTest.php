<?php

use App\Proton\Config;
use App\Proton\Data;
use App\Proton\Page;
use App\Proton\PageBatchWriter;
use Tests\Helpers\TestFixtures;

uses(TestFixtures::class);

beforeEach(function (): void {
    $this->setUpTempProject();
});

afterEach(function (): void {
    $this->tearDownTempProject();
});

function createBatchTwig(Config $config, Page $page): Twig\Environment
{
    $paths = $config->settings->paths;

    $templateLoader = new Twig\Loader\FilesystemLoader([
        $paths->partials,
        $paths->macros,
    ]);
    $templateLoader->addPath($paths->pages, 'pages');
    $templateLoader->addPath($paths->layouts, 'layouts');

    $pageLoader  = new Twig\Loader\ArrayLoader(["@pages/{$page->name}" => $page->content]);
    $chainLoader = new Twig\Loader\ChainLoader([$pageLoader, $templateLoader]);

    return new Twig\Environment($chainLoader, ['cache' => false]);
}

test('batch writer creates file for each batch item', function (): void {
    $this->createDataFile('data.yml', [
        'title' => 'Test',
        'team'  => [
            'alice' => ['name' => 'Alice'],
            'bob'   => ['name' => 'Bob'],
        ],
    ]);
    $this->createPage('member.html', '<h1>{{ batch.name }}</h1>', [
        'layout' => 'none',
        'batch'  => 'team',
    ]);

    $config = new Config();
    $data   = new Data($config);
    $page   = new Page('member.html', $config, $data);
    $twig   = createBatchTwig($config, $page);

    $writer = new PageBatchWriter($page, $twig, $config);
    $writer->processBatch();

    // autoindex is on, so alice/index.html and bob/index.html
    expect(file_exists($this->tempDir . '/dist/alice/index.html'))->toBeTrue();
    expect(file_exists($this->tempDir . '/dist/bob/index.html'))->toBeTrue();
});

test('batch pages contain correct data', function (): void {
    $this->createDataFile('data.yml', [
        'title'  => 'Test',
        'people' => [
            'john' => ['name' => 'John Doe'],
            'jane' => ['name' => 'Jane Doe'],
        ],
    ]);
    $this->createPage('person.html', '<span>{{ batch.name }}</span>', [
        'layout' => 'none',
        'batch'  => 'people',
    ]);

    $config = new Config();
    $data   = new Data($config);
    $page   = new Page('person.html', $config, $data);
    $twig   = createBatchTwig($config, $page);

    $writer = new PageBatchWriter($page, $twig, $config);
    $writer->processBatch();

    $johnContent = file_get_contents($this->tempDir . '/dist/john/index.html');
    $janeContent = file_get_contents($this->tempDir . '/dist/jane/index.html');

    expect($johnContent)->toContain('John Doe');
    expect($janeContent)->toContain('Jane Doe');
});

test('batch pages have correct page metadata per item', function (): void {
    $this->createConfigFile(['domain' => 'https://test.com']);
    $this->createDataFile('data.yml', [
        'title'  => 'Test',
        'people' => [
            'john' => ['name' => 'John Doe'],
            'jane' => ['name' => 'Jane Doe'],
        ],
    ]);
    $this->createPage('person.html', '{{ page.canonical }}|{{ page.url }}', [
        'layout' => 'none',
        'batch'  => 'people',
    ]);

    $config = new Config();
    $data   = new Data($config);
    $page   = new Page('person.html', $config, $data);
    $twig   = createBatchTwig($config, $page);

    $writer = new PageBatchWriter($page, $twig, $config);
    $writer->processBatch();

    $johnContent = file_get_contents($this->tempDir . '/dist/john/index.html');
    $janeContent = file_get_contents($this->tempDir . '/dist/jane/index.html');

    expect($johnContent)->toContain('https://test.com/john/');
    expect($johnContent)->toContain('/john/');
    expect($janeContent)->toContain('https://test.com/jane/');
    expect($janeContent)->toContain('/jane/');
});

test('batch pages have correct title per item', function (): void {
    $this->createDataFile('data.yml', [
        'title'  => 'Test',
        'people' => [
            'john' => ['name' => 'John Doe'],
            'jane' => ['name' => 'Jane Doe'],
        ],
    ]);
    $this->createPage('person.html', '{{ page.title }}', [
        'layout' => 'none',
        'batch'  => 'people',
    ]);

    $config = new Config();
    $data   = new Data($config);
    $page   = new Page('person.html', $config, $data);
    $twig   = createBatchTwig($config, $page);

    $writer = new PageBatchWriter($page, $twig, $config);
    $writer->processBatch();

    $johnContent = file_get_contents($this->tempDir . '/dist/john/index.html');
    $janeContent = file_get_contents($this->tempDir . '/dist/jane/index.html');

    // Title should be derived from batch key, not template filename
    expect($johnContent)->toContain('John');
    expect($janeContent)->toContain('Jane');
    expect($johnContent)->not->toContain('Person');
    expect($janeContent)->not->toContain('Person');
});

test('batch pages have correct outputPath per item', function (): void {
    $this->createDataFile('data.yml', [
        'title'  => 'Test',
        'people' => [
            'john' => ['name' => 'John Doe'],
        ],
    ]);
    $this->createPage('person.html', '{{ page.outputPath }}|{{ page.filename }}|{{ page.isIndex }}', [
        'layout' => 'none',
        'batch'  => 'people',
    ]);

    $config = new Config();
    $data   = new Data($config);
    $page   = new Page('person.html', $config, $data);
    $twig   = createBatchTwig($config, $page);

    $writer = new PageBatchWriter($page, $twig, $config);
    $writer->processBatch();

    $content = file_get_contents($this->tempDir . '/dist/john/index.html');

    expect($content)->toContain('john/index.html');
    expect($content)->toContain('|john|');
    // isIndex should be false (batch key is "john", not "index")
    expect($content)->not->toContain('|1');
});

test('batch pages preserve custom frontmatter fields', function (): void {
    $this->createDataFile('data.yml', [
        'title'  => 'Test',
        'people' => [
            'john' => ['name' => 'John Doe'],
        ],
    ]);
    $this->createPage('person.html', '{{ page.nav_group }}|{{ page.custom }}', [
        'layout'    => 'none',
        'batch'     => 'people',
        'nav_group' => 'Team',
        'custom'    => 'hello',
    ]);

    $config = new Config();
    $data   = new Data($config);
    $page   = new Page('person.html', $config, $data);
    $twig   = createBatchTwig($config, $page);

    $writer = new PageBatchWriter($page, $twig, $config);
    $writer->processBatch();

    $content = file_get_contents($this->tempDir . '/dist/john/index.html');

    expect($content)->toContain('Team');
    expect($content)->toContain('hello');
});

test('batch pages have correct depth and parent', function (): void {
    $this->createDataFile('data.yml', [
        'title' => 'Test',
        'staff' => [
            'alice' => ['name' => 'Alice'],
        ],
    ]);
    $this->createPage('team/member.html', '{{ page.depth }}|{{ page.parent }}', [
        'layout' => 'none',
        'batch'  => 'staff',
    ]);

    $config = new Config();
    $data   = new Data($config);
    $page   = new Page('team/member.html', $config, $data);
    $twig   = createBatchTwig($config, $page);

    $writer = new PageBatchWriter($page, $twig, $config);
    $writer->processBatch();

    $content = file_get_contents($this->tempDir . '/dist/team/alice/index.html');

    expect($content)->toContain('1|/team/');
});

test('batch writer with nested page directory', function (): void {
    $this->createDataFile('data.yml', [
        'title' => 'Test',
        'staff' => [
            'alice' => ['name' => 'Alice'],
        ],
    ]);
    $this->createPage('team/member.html', '<h1>{{ batch.name }}</h1>', [
        'layout' => 'none',
        'batch'  => 'staff',
    ]);

    $config = new Config();
    $data   = new Data($config);
    $page   = new Page('team/member.html', $config, $data);
    $twig   = createBatchTwig($config, $page);

    $writer = new PageBatchWriter($page, $twig, $config);
    $writer->processBatch();

    expect(file_exists($this->tempDir . '/dist/team/alice/index.html'))->toBeTrue();
});
