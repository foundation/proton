<?php

use App\Proton\Config;
use App\Proton\Data;
use Tests\Helpers\TestFixtures;

uses(TestFixtures::class);

beforeEach(function (): void {
    $this->setUpTempProject();
});

afterEach(function (): void {
    $this->tearDownTempProject();
});

test('loads data from yaml files', function (): void {
    $this->createDataFile('data.yml', ['title' => 'My Site', 'version' => '1.0']);

    $config = new Config();
    $data   = new Data($config);

    expect($data->data['title'])->toBe('My Site');
    expect($data->data['version'])->toBe('1.0');
});

test('nested data file creates hierarchy', function (): void {
    $this->createDataFile('blog/posts.yml', ['post1' => ['title' => 'First Post']]);

    $config = new Config();
    $data   = new Data($config);

    expect($data->data['blog']['posts'])->toBe(['post1' => ['title' => 'First Post']]);
});

test('getDataPath strips dir prefix and extension', function (): void {
    $this->createDataFile('data.yml', ['key' => 'value']);

    $config = new Config();
    $data   = new Data($config);

    // Data uses relative path (src/data), so SplFileInfo must match
    $file = new SplFileInfo('src/data/data.yml');
    $path = $data->getDataPath($file);

    expect($path)->toBe('data');
});

test('getDataPath preserves nested path', function (): void {
    $this->createDataFile('blog/posts.yml', ['key' => 'value']);

    $config = new Config();
    $data   = new Data($config);

    $file = new SplFileInfo('src/data/blog/posts.yml');
    $path = $data->getDataPath($file);

    expect($path)->toBe('blog/posts');
});

test('generatePageData merges data env and page', function (): void {
    $this->createDataFile('data.yml', ['title' => 'Site']);

    $config = new Config();
    $data   = new Data($config);
    $result = $data->generatePageData(['heading' => 'Hello']);

    expect($result)->toHaveKeys(['data', 'proton', 'page']);
    expect($result['data']['title'])->toBe('Site');
    expect($result['page']['heading'])->toBe('Hello');
    expect($result['proton'])->toHaveKey('environment');
});

test('refresh reloads data', function (): void {
    $this->createDataFile('data.yml', ['title' => 'Original']);

    $config = new Config();
    $data   = new Data($config);

    expect($data->data['title'])->toBe('Original');

    // Update the file
    $this->createDataFile('data.yml', ['title' => 'Updated']);
    $data->refresh();

    expect($data->data['title'])->toBe('Updated');
});

test('skips dot files', function (): void {
    $this->createDataFile('data.yml', ['title' => 'Site']);
    // Create a dot file that should be ignored
    file_put_contents($this->tempDir . '/src/data/.hidden.yml', "secret: true\n");

    $config = new Config();
    $data   = new Data($config);

    expect($data->data)->toHaveKey('title');
    expect($data->data)->not->toHaveKey('secret');
});

test('env data includes environment', function (): void {
    $this->createDataFile('data.yml', ['key' => 'value']);

    $config = new Config();
    $data   = new Data($config);

    expect($data->env)->toHaveKey('environment');
});

test('multiple data files merge at root for default name', function (): void {
    $this->createDataFile('data.yml', ['title' => 'Site', 'version' => '1.0']);

    $config = new Config();
    $data   = new Data($config);

    expect($data->data['title'])->toBe('Site');
    expect($data->data['version'])->toBe('1.0');
});

test('multiple data files coexist', function (): void {
    $this->createDataFile('data.yml', ['title' => 'Site']);
    $this->createDataFile('extra.yml', ['color' => 'blue']);

    $config = new Config();
    $data   = new Data($config);

    expect($data->data['title'])->toBe('Site');
    expect($data->data['extra'])->toBe(['color' => 'blue']);
});

test('deeply nested data files create hierarchy', function (): void {
    $this->createDataFile('a/b/c.yml', ['deep' => true]);

    $config = new Config();
    $data   = new Data($config);

    expect($data->data['a']['b']['c'])->toBe(['deep' => true]);
});

test('env data includes build time', function (): void {
    $this->createDataFile('data.yml', ['key' => 'value']);

    $config = new Config();
    $data   = new Data($config);

    expect($data->env)->toHaveKey('build_time');
    expect($data->env['build_time'])->toBeInt();
});

test('generatePageData with empty page data', function (): void {
    $this->createDataFile('data.yml', ['title' => 'Site']);

    $config = new Config();
    $data   = new Data($config);
    $result = $data->generatePageData([]);

    expect($result['page'])->toBe([]);
    expect($result['data']['title'])->toBe('Site');
});

// --- JSON data file tests ---

test('loads data from json files', function (): void {
    $this->createJsonDataFile('info.json', ['version' => '2.0', 'author' => 'Joe']);

    $config = new Config();
    $data   = new Data($config);

    expect($data->data['info'])->toBe(['version' => '2.0', 'author' => 'Joe']);
});

test('json data file named data creates root data', function (): void {
    $this->createJsonDataFile('data.json', ['title' => 'JSON Site']);

    $config = new Config();
    $data   = new Data($config);

    expect($data->data['title'])->toBe('JSON Site');
});

test('nested json data file creates hierarchy', function (): void {
    $this->createJsonDataFile('api/endpoints.json', ['users' => '/api/users']);

    $config = new Config();
    $data   = new Data($config);

    expect($data->data['api']['endpoints'])->toBe(['users' => '/api/users']);
});

test('json and yaml data files coexist', function (): void {
    $this->createDataFile('data.yml', ['title' => 'Site']);
    $this->createJsonDataFile('config.json', ['theme' => 'dark']);

    $config = new Config();
    $data   = new Data($config);

    expect($data->data['title'])->toBe('Site');
    expect($data->data['config'])->toBe(['theme' => 'dark']);
});

test('invalid json data file throws ConfigException', function (): void {
    file_put_contents($this->tempDir . '/src/data/bad.json', '{invalid json}');

    $config = new Config();

    expect(fn (): Data => new Data($config))->toThrow(App\Proton\Exceptions\ConfigException::class);
});

test('unsupported data file extensions are ignored', function (): void {
    $this->createDataFile('data.yml', ['title' => 'Site']);
    file_put_contents($this->tempDir . '/src/data/readme.txt', 'ignore me');

    $config = new Config();
    $data   = new Data($config);

    expect($data->data)->not->toHaveKey('readme');
    expect($data->data['title'])->toBe('Site');
});
