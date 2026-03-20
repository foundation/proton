<?php

use App\Proton\Config;
use App\Proton\Data;
use App\Proton\Exceptions\BuildException;
use App\Proton\Exceptions\ConfigException;
use App\Proton\Exceptions\FilesystemException;
use App\Proton\FilesystemManager;
use App\Proton\Page;
use Tests\Helpers\TestFixtures;

uses(TestFixtures::class);

beforeEach(function (): void {
    $this->setUpTempProject();
});

afterEach(function (): void {
    $this->tearDownTempProject();
});

// --- ConfigException ---

test('Config throws ConfigException on invalid YAML', function (): void {
    file_put_contents($this->tempDir . '/proton.yml', "invalid: yaml: [unterminated\n");

    expect(fn (): Config => new Config())->toThrow(ConfigException::class);
});

test('Data throws ConfigException on invalid YAML data file', function (): void {
    file_put_contents($this->tempDir . '/src/data/bad.yml', "invalid: yaml: [unterminated\n");

    $config = new Config();

    expect(fn (): Data => new Data($config))->toThrow(ConfigException::class);
});

test('ConfigException message includes file path for data files', function (): void {
    file_put_contents($this->tempDir . '/src/data/broken.yml', "bad: yaml: [nope\n");

    $config = new Config();

    try {
        new Data($config);
        $this->fail('Expected ConfigException');
    } catch (ConfigException $e) {
        expect($e->getMessage())->toContain('broken.yml');
    }
});

test('ConfigException message includes file path for config files', function (): void {
    file_put_contents($this->tempDir . '/proton.yml', "bad: yaml: [nope\n");

    try {
        new Config();
        $this->fail('Expected ConfigException');
    } catch (ConfigException $e) {
        expect($e->getMessage())->toContain('proton.yml');
    }
});

// --- FilesystemException ---

test('FilesystemManager pathChecker throws FilesystemException', function (): void {
    rmdir($this->tempDir . '/src/macros');

    $config    = new Config();
    $fsManager = new FilesystemManager($config);

    expect(fn (): bool => $fsManager->pathChecker())->toThrow(FilesystemException::class);
});

test('FilesystemException contains helpful message', function (): void {
    rmdir($this->tempDir . '/src/macros');

    $config    = new Config();
    $fsManager = new FilesystemManager($config);

    try {
        $fsManager->pathChecker();
        $this->fail('Expected FilesystemException');
    } catch (FilesystemException $e) {
        expect($e->getMessage())->toContain('proton init');
    }
});

// --- BuildException ---

test('Page throws BuildException for missing page file', function (): void {
    $config = new Config();
    $data   = new Data($config);

    expect(fn (): Page => new Page('nonexistent.html', $config, $data))->toThrow(BuildException::class);
});

test('BuildException message includes page path', function (): void {
    $config = new Config();
    $data   = new Data($config);

    try {
        new Page('missing-page.html', $config, $data);
        $this->fail('Expected BuildException');
    } catch (BuildException $e) {
        expect($e->getMessage())->toContain('missing-page.html');
    }
});

// --- Exception hierarchy ---

test('ConfigException is a RuntimeException', function (): void {
    $e = new ConfigException('test');
    expect($e)->toBeInstanceOf(RuntimeException::class);
});

test('FilesystemException is a RuntimeException', function (): void {
    $e = new FilesystemException('test');
    expect($e)->toBeInstanceOf(RuntimeException::class);
});

test('BuildException is a RuntimeException', function (): void {
    $e = new BuildException('test');
    expect($e)->toBeInstanceOf(RuntimeException::class);
});
