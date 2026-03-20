<?php

use Tests\Helpers\TestFixtures;

uses(TestFixtures::class);

beforeEach(function (): void {
    $this->setUpTempProject();
});

afterEach(function (): void {
    $this->tearDownTempProject();
});

test('init command creates folders', function (): void {
    // Remove a directory to verify init recreates it
    rmdir($this->tempDir . '/src/macros');

    $this->artisan('init')
         ->expectsOutputToContain('Init Complete')
         ->assertExitCode(0);

    expect(is_dir($this->tempDir . '/src/macros'))->toBeTrue();
});

test('init with config flag creates config file', function (): void {
    $this->artisan('init', ['--config' => true])
         ->expectsOutputToContain('Initiating Proton config')
         ->assertExitCode(0);

    expect(file_exists($this->tempDir . '/proton.yml'))->toBeTrue();
});

test('init with config flag reports existing config', function (): void {
    $this->createConfigFile(['domain' => 'https://test.com']);

    $this->artisan('init', ['--config' => true])
         ->expectsOutputToContain('Config already exists')
         ->assertExitCode(0);
});
