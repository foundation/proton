<?php

use App\Proton\Config;
use Tests\Helpers\TestFixtures;

uses(TestFixtures::class);

beforeEach(function () {
    $this->setUpTempProject();
});

afterEach(function () {
    $this->tearDownTempProject();
});

test('defaults are set when no config file exists', function () {
    $config = new Config();

    expect($config->settings->defaultExt)->toBe('html');
    expect($config->settings->domain)->toBe('https://www.example.com');
    expect($config->settings->autoindex)->toBeTrue();
    expect($config->settings->debug)->toBeFalse();
    expect($config->settings->pretty)->toBeTrue();
    expect($config->settings->minify)->toBeFalse();
    expect($config->settings->sitemap)->toBeTrue();
});

test('config file values override defaults', function () {
    $this->createConfigFile([
        'domain' => 'https://mysite.com',
        'autoindex' => false,
        'defaultExt' => 'php',
    ]);

    $config = new Config();

    expect($config->settings->domain)->toBe('https://mysite.com');
    expect($config->settings->autoindex)->toBeFalse();
    expect($config->settings->defaultExt)->toBe('php');
    // Defaults still present for unspecified keys
    expect($config->settings->pretty)->toBeTrue();
});

test('dot proton yml is supported', function () {
    file_put_contents($this->tempDir . '/.proton.yml', "domain: https://hidden.com\n");

    $config = new Config();

    expect($config->settings->domain)->toBe('https://hidden.com');
});

test('proton yml takes precedence over dot proton yml', function () {
    $this->createConfigFile(['domain' => 'https://primary.com']);
    file_put_contents($this->tempDir . '/.proton.yml', "domain: https://secondary.com\n");

    $config = new Config();

    expect($config->settings->domain)->toBe('https://primary.com');
});

test('configFileExists returns true when file exists', function () {
    $this->createConfigFile(['domain' => 'https://test.com']);

    $config = new Config();

    expect($config->configFileExists())->toBeTrue();
});

test('configFileExists returns false when no file exists', function () {
    $config = new Config();

    expect($config->configFileExists())->toBeFalse();
});

test('initConfigFile creates proton yml', function () {
    $config = new Config();
    $result = $config->initConfigFile();

    expect($result)->toBeTrue();
    expect(file_exists($this->tempDir . '/proton.yml'))->toBeTrue();
});

test('initConfigFile returns false when config already exists', function () {
    $this->createConfigFile(['domain' => 'https://existing.com']);

    $config = new Config();
    $result = $config->initConfigFile();

    expect($result)->toBeFalse();
});

test('default paths are configured', function () {
    $config = new Config();

    expect($config->settings->paths->dist)->toBe('dist');
    expect($config->settings->paths->pages)->toBe('src/pages');
    expect($config->settings->paths->layouts)->toBe('src/layouts');
    expect($config->settings->paths->data)->toBe('src/data');
    expect($config->settings->paths->assets)->toBe('src/assets');
    expect($config->settings->paths->partials)->toBe('src/partials');
    expect($config->settings->paths->macros)->toBe('src/macros');
});

test('default layouts are configured', function () {
    $config = new Config();

    expect($config->settings->layouts->default)->toBe('default.html');
});
