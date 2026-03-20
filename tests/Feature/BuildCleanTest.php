<?php

use Tests\Helpers\TestFixtures;

uses(TestFixtures::class);

beforeEach(function (): void {
    $this->setUpTempProject(['npmBuild' => '']);
    $this->createDataFile('data.yml', ['title' => 'Test']);
    $this->createPage('index.html', '<h1>Home</h1>', ['layout' => 'none']);
});

afterEach(function (): void {
    $this->tearDownTempProject();
});

test('build with clean flag removes previous dist', function (): void {
    // Create stale file in dist
    file_put_contents($this->tempDir . '/dist/stale.html', 'old');

    $this->artisan('build', ['--clean' => true])
         ->expectsOutputToContain('Cleaning previous builds')
         ->expectsOutputToContain('Build Complete')
         ->assertExitCode(0);

    // Stale file should be gone, but new build output present
    expect(file_exists($this->tempDir . '/dist/stale.html'))->toBeFalse();
    expect(file_exists($this->tempDir . '/dist/index.html'))->toBeTrue();
});

test('build without clean preserves existing dist files', function (): void {
    file_put_contents($this->tempDir . '/dist/existing.html', 'keep');

    $this->artisan('build')
         ->expectsOutputToContain('Build Complete')
         ->assertExitCode(0);

    expect(file_exists($this->tempDir . '/dist/existing.html'))->toBeTrue();
    expect(file_exists($this->tempDir . '/dist/index.html'))->toBeTrue();
});
