<?php

use App\Proton\FileScanner;
use Tests\Helpers\TestFixtures;

uses(TestFixtures::class);

beforeEach(function (): void {
    $this->setUpTempProject();
});

afterEach(function (): void {
    $this->tearDownTempProject();
});

test('snapshot captures current files', function (): void {
    $this->createPage('index.html', '<h1>Home</h1>');

    $scanner = new FileScanner([$this->tempDir . '/src/pages']);
    $scanner->snapshot();

    // No changes immediately after snapshot
    $changes = $scanner->scan();
    expect($changes)->toBe([]);
});

test('scan detects new file', function (): void {
    $scanner = new FileScanner([$this->tempDir . '/src/pages']);
    $scanner->snapshot();

    $this->createPage('about.html', '<h1>About</h1>');

    $changes = $scanner->scan();
    expect($changes)->toHaveCount(1);
    expect($changes[0]['type'])->toBe(FileScanner::EVENT_FILE_CREATED);
    expect($changes[0]['path'])->toContain('about.html');
});

test('scan detects updated file', function (): void {
    $this->createPage('index.html', '<h1>Home</h1>');

    $scanner = new FileScanner([$this->tempDir . '/src/pages']);
    $scanner->snapshot();

    // Touch with future mtime to ensure change is detected
    sleep(1);
    file_put_contents($this->tempDir . '/src/pages/index.html', '<h1>Updated</h1>');

    $changes = $scanner->scan();
    expect($changes)->toHaveCount(1);
    expect($changes[0]['type'])->toBe(FileScanner::EVENT_FILE_UPDATED);
    expect($changes[0]['path'])->toContain('index.html');
});

test('scan detects deleted file', function (): void {
    $this->createPage('index.html', '<h1>Home</h1>');

    $scanner = new FileScanner([$this->tempDir . '/src/pages']);
    $scanner->snapshot();

    unlink($this->tempDir . '/src/pages/index.html');

    $changes = $scanner->scan();
    expect($changes)->toHaveCount(1);
    expect($changes[0]['type'])->toBe(FileScanner::EVENT_FILE_DELETED);
    expect($changes[0]['path'])->toContain('index.html');
});

test('scan detects multiple changes', function (): void {
    $this->createPage('index.html', '<h1>Home</h1>');
    $this->createPage('about.html', '<h1>About</h1>');

    $scanner = new FileScanner([$this->tempDir . '/src/pages']);
    $scanner->snapshot();

    // Delete one, create another
    unlink($this->tempDir . '/src/pages/about.html');
    $this->createPage('contact.html', '<h1>Contact</h1>');

    $changes = $scanner->scan();
    $types   = array_column($changes, 'type');

    expect($changes)->toHaveCount(2);
    expect($types)->toContain(FileScanner::EVENT_FILE_DELETED);
    expect($types)->toContain(FileScanner::EVENT_FILE_CREATED);
});

test('scan returns empty when no changes', function (): void {
    $this->createPage('index.html', '<h1>Home</h1>');

    $scanner = new FileScanner([$this->tempDir . '/src/pages']);
    $scanner->snapshot();

    $changes = $scanner->scan();
    expect($changes)->toBe([]);
});

test('scan watches multiple paths', function (): void {
    $scanner = new FileScanner([
        $this->tempDir . '/src/pages',
        $this->tempDir . '/src/assets',
    ]);
    $scanner->snapshot();

    $this->createPage('new.html', '<h1>New</h1>');
    $this->createAsset('new.css', 'body {}');

    $changes = $scanner->scan();
    expect($changes)->toHaveCount(2);
});

test('scan skips dot files', function (): void {
    $scanner = new FileScanner([$this->tempDir . '/src/pages']);
    $scanner->snapshot();

    file_put_contents($this->tempDir . '/src/pages/.hidden', 'secret');

    $changes = $scanner->scan();
    expect($changes)->toBe([]);
});

test('scan handles non-existent watch path gracefully', function (): void {
    $scanner = new FileScanner([$this->tempDir . '/nonexistent']);
    $scanner->snapshot();

    $changes = $scanner->scan();
    expect($changes)->toBe([]);
});

test('subsequent scans update baseline', function (): void {
    $scanner = new FileScanner([$this->tempDir . '/src/pages']);
    $scanner->snapshot();

    // First change
    $this->createPage('first.html', '<h1>First</h1>');
    $changes1 = $scanner->scan();
    expect($changes1)->toHaveCount(1);

    // Second scan with no new changes
    $changes2 = $scanner->scan();
    expect($changes2)->toBe([]);

    // Third change
    $this->createPage('second.html', '<h1>Second</h1>');
    $changes3 = $scanner->scan();
    expect($changes3)->toHaveCount(1);
    expect($changes3[0]['path'])->toContain('second.html');
});
