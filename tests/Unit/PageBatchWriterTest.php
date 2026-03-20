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
