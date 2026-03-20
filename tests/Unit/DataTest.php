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
