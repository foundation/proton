<?php

use App\Proton\Config;
use App\Proton\Data;
use App\Proton\Page;
use App\Proton\PageWriter;
use Tests\Helpers\TestFixtures;

uses(TestFixtures::class);

beforeEach(function (): void {
    $this->setUpTempProject();
    $this->createDataFile('data.yml', ['title' => 'Test Site']);
});

afterEach(function (): void {
    $this->tearDownTempProject();
});

function createTwig(Config $config, Page $page): Twig\Environment
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

    $twig = new Twig\Environment($chainLoader, ['cache' => false]);
    $twig->addExtension(new Twig\Extra\Markdown\MarkdownExtension());
    $twig->addRuntimeLoader(new class implements Twig\RuntimeLoader\RuntimeLoaderInterface {
        public function load(string $class): ?object
        {
            if ($class === Twig\Extra\Markdown\MarkdownRuntime::class) {
                return new Twig\Extra\Markdown\MarkdownRuntime(
                    new Twig\Extra\Markdown\MichelfMarkdown()
                );
            }

            return null;
        }
    });

    return $twig;
}

test('savePage creates output file', function (): void {
    $this->createPage('index.html', '<h1>Hello</h1>', ['layout' => 'none']);

    $config = new Config();
    $data   = new Data($config);
    $page   = new Page('index.html', $config, $data);
    $twig   = createTwig($config, $page);

    $writer = new PageWriter($page, $twig, $config);
    $writer->savePage();

    // autoindex is on by default, but index pages don't get double-indexed
    expect(file_exists($this->tempDir . '/dist/index.html'))->toBeTrue();
});

test('autoindex creates subdirectory for non-index pages', function (): void {
    $this->createPage('about.html', '<h1>About</h1>', ['layout' => 'none']);

    $config = new Config();
    $data   = new Data($config);
    $page   = new Page('about.html', $config, $data);
    $twig   = createTwig($config, $page);

    $writer = new PageWriter($page, $twig, $config);
    $writer->savePage();

    expect(file_exists($this->tempDir . '/dist/about/index.html'))->toBeTrue();
});

test('autoindex disabled writes flat files', function (): void {
    $this->createConfigFile(['autoindex' => false]);
    $this->createPage('about.html', '<h1>About</h1>', ['layout' => 'none']);

    $config = new Config();
    $data   = new Data($config);
    $page   = new Page('about.html', $config, $data);
    $twig   = createTwig($config, $page);

    $writer = new PageWriter($page, $twig, $config);
    $writer->savePage();

    expect(file_exists($this->tempDir . '/dist/about.html'))->toBeTrue();
});

test('markdown extension maps to default ext', function (): void {
    $this->createPage('post.md', '# Hello', ['layout' => 'none']);

    $config = new Config();
    $data   = new Data($config);
    $page   = new Page('post.md', $config, $data);
    $twig   = createTwig($config, $page);

    $writer = new PageWriter($page, $twig, $config);
    $writer->savePage();

    // md → html (defaultExt), with autoindex: post/index.html
    expect(file_exists($this->tempDir . '/dist/post/index.html'))->toBeTrue();
});

test('twig extension maps to default ext', function (): void {
    $this->createPage('page.twig', '<p>Twig page</p>', ['layout' => 'none']);

    $config = new Config();
    $data   = new Data($config);
    $page   = new Page('page.twig', $config, $data);
    $twig   = createTwig($config, $page);

    $writer = new PageWriter($page, $twig, $config);
    $writer->savePage();

    expect(file_exists($this->tempDir . '/dist/page/index.html'))->toBeTrue();
});

test('custom output name from page data', function (): void {
    $this->createPage('feed.html', '<rss></rss>', ['layout' => 'none', 'output' => 'feed.xml']);

    $config = new Config();
    $data   = new Data($config);
    $page   = new Page('feed.html', $config, $data);
    $twig   = createTwig($config, $page);

    $writer = new PageWriter($page, $twig, $config);
    $writer->savePage();

    expect(file_exists($this->tempDir . '/dist/feed.xml'))->toBeTrue();
});

test('nested page preserves directory', function (): void {
    $this->createPage('blog/post.html', '<p>Post</p>', ['layout' => 'none']);

    $config = new Config();
    $data   = new Data($config);
    $page   = new Page('blog/post.html', $config, $data);
    $twig   = createTwig($config, $page);

    $writer = new PageWriter($page, $twig, $config);
    $writer->savePage();

    expect(file_exists($this->tempDir . '/dist/blog/post/index.html'))->toBeTrue();
});

test('pretty output indents html', function (): void {
    $this->createPage('index.html', '<html><body><div><p>Hello</p></div></body></html>', ['layout' => 'none']);

    $config = new Config();
    $data   = new Data($config);
    $page   = new Page('index.html', $config, $data);
    $twig   = createTwig($config, $page);

    $writer = new PageWriter($page, $twig, $config);
    $writer->savePage();

    $output = file_get_contents($this->tempDir . '/dist/index.html');
    // Pretty output should contain indentation
    expect($output)->toContain("\n");
});

test('minified output removes whitespace', function (): void {
    $this->createConfigFile(['minify' => true, 'pretty' => false]);
    $this->createDataFile('data.yml', ['title' => 'Test Site']);
    $this->createPage('index.html', "<html>\n<body>\n  <div>\n    <p>Hello</p>\n  </div>\n</body>\n</html>", ['layout' => 'none']);

    $config = new Config();
    $data   = new Data($config);
    $page   = new Page('index.html', $config, $data);
    $twig   = createTwig($config, $page);

    $writer = new PageWriter($page, $twig, $config);
    $writer->savePage();

    $output = file_get_contents($this->tempDir . '/dist/index.html');
    // Minified output should not have leading indentation
    expect($output)->not->toContain('  <div>');
});

test('non-pretty non-minified output passes through unchanged', function (): void {
    $this->createConfigFile(['minify' => false, 'pretty' => false]);
    $this->createDataFile('data.yml', ['title' => 'Test Site']);
    $this->createPage('index.html', '<p>Hello</p>', ['layout' => 'none']);

    $config = new Config();
    $data   = new Data($config);
    $page   = new Page('index.html', $config, $data);
    $twig   = createTwig($config, $page);

    $writer = new PageWriter($page, $twig, $config);
    $writer->savePage();

    $output = file_get_contents($this->tempDir . '/dist/index.html');
    expect($output)->toBe('<p>Hello</p>');
});

test('custom extension is preserved in output path', function (): void {
    $this->createConfigFile(['autoindex' => false]);
    $this->createDataFile('data.yml', ['title' => 'Test Site']);
    $this->createPage('robots.txt', 'User-agent: *', ['layout' => 'none']);

    $config = new Config();
    $data   = new Data($config);
    $page   = new Page('robots.txt', $config, $data);
    $twig   = createTwig($config, $page);

    $writer = new PageWriter($page, $twig, $config);
    $writer->savePage();

    expect(file_exists($this->tempDir . '/dist/robots.txt'))->toBeTrue();
});

test('index page is not double-indexed with autoindex', function (): void {
    $this->createPage('blog/index.html', '<h1>Blog</h1>', ['layout' => 'none']);

    $config = new Config();
    $data   = new Data($config);
    $page   = new Page('blog/index.html', $config, $data);
    $twig   = createTwig($config, $page);

    $writer = new PageWriter($page, $twig, $config);
    $writer->savePage();

    expect(file_exists($this->tempDir . '/dist/blog/index.html'))->toBeTrue();
    // Should NOT create blog/index/index.html
    expect(file_exists($this->tempDir . '/dist/blog/index/index.html'))->toBeFalse();
});
