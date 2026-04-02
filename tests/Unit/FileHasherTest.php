<?php

use App\Proton\FileHasher;

beforeEach(function (): void {
    $this->tempDir = sys_get_temp_dir() . '/proton-hasher-' . uniqid();
    mkdir($this->tempDir, 0777, true);
});

afterEach(function (): void {
    array_map('unlink', glob($this->tempDir . '/*') ?: []);
    rmdir($this->tempDir);
});

test('hash returns 8-char md5 prefix', function (): void {
    file_put_contents($this->tempDir . '/app.js', 'console.log("hello")');

    $hasher = new FileHasher($this->tempDir);
    $hash   = $hasher->hash('app.js');

    expect($hash)->toHaveLength(8);
    expect($hash)->toBe(substr(md5_file($this->tempDir . '/app.js'), 0, 8));
});

test('hash changes when file content changes', function (): void {
    file_put_contents($this->tempDir . '/app.js', 'version1');
    $hasher = new FileHasher($this->tempDir);
    $hash1  = $hasher->hash('app.js');

    file_put_contents($this->tempDir . '/app.js', 'version2');
    $hash2 = $hasher->hash('app.js');

    expect($hash1)->not->toBe($hash2);
});

test('hash returns empty string for missing file', function (): void {
    $hasher = new FileHasher($this->tempDir);

    expect($hasher->hash('missing.js'))->toBe('');
});

test('integrity returns sha384 SRI string by default', function (): void {
    $content = 'body { color: red; }';
    file_put_contents($this->tempDir . '/style.css', $content);

    $hasher = new FileHasher($this->tempDir);
    $result = $hasher->integrity('style.css');

    $expected = 'sha384-' . base64_encode(hash('sha384', $content, true));
    expect($result)->toBe($expected);
});

test('integrity supports sha256 algorithm', function (): void {
    $content = 'var x = 1;';
    file_put_contents($this->tempDir . '/app.js', $content);

    $hasher = new FileHasher($this->tempDir);
    $result = $hasher->integrity('app.js', 'sha256');

    $expected = 'sha256-' . base64_encode(hash('sha256', $content, true));
    expect($result)->toBe($expected);
});

test('integrity returns empty string for missing file', function (): void {
    $hasher = new FileHasher($this->tempDir);

    expect($hasher->integrity('missing.css'))->toBe('');
});

test('hash resolves paths with leading slash', function (): void {
    file_put_contents($this->tempDir . '/app.js', 'test');

    $hasher = new FileHasher($this->tempDir);

    expect($hasher->hash('/app.js'))->toBe($hasher->hash('app.js'));
});

test('stylesheet generates link tag with cache but no integrity by default', function (): void {
    $content = 'body { color: red; }';
    file_put_contents($this->tempDir . '/style.css', $content);

    $hasher = new FileHasher($this->tempDir);
    $tag    = $hasher->stylesheet('style.css');

    $hash = substr(md5($content), 0, 8);

    expect($tag)->toBe("<link href=\"style.css?cache=$hash\" rel=\"stylesheet\">");
    expect($tag)->not->toContain('integrity');
});

test('stylesheet includes integrity when enabled', function (): void {
    $content = 'body { color: red; }';
    file_put_contents($this->tempDir . '/style.css', $content);

    $hasher = new FileHasher($this->tempDir);
    $tag    = $hasher->stylesheet('style.css', true);

    $hash      = substr(md5($content), 0, 8);
    $integrity = 'sha384-' . base64_encode(hash('sha384', $content, true));

    expect($tag)->toBe("<link href=\"style.css?cache=$hash\" rel=\"stylesheet\" integrity=\"$integrity\">");
});

test('script generates script tag with cache but no integrity by default', function (): void {
    $content = 'console.log("hi")';
    file_put_contents($this->tempDir . '/app.js', $content);

    $hasher = new FileHasher($this->tempDir);
    $tag    = $hasher->script('app.js');

    $hash = substr(md5($content), 0, 8);

    expect($tag)->toBe("<script src=\"app.js?cache=$hash\"></script>");
    expect($tag)->not->toContain('integrity');
});

test('script includes integrity when enabled', function (): void {
    $content = 'console.log("hi")';
    file_put_contents($this->tempDir . '/app.js', $content);

    $hasher = new FileHasher($this->tempDir);
    $tag    = $hasher->script('app.js', true);

    $hash      = substr(md5($content), 0, 8);
    $integrity = 'sha384-' . base64_encode(hash('sha384', $content, true));

    expect($tag)->toBe("<script src=\"app.js?cache=$hash\" integrity=\"$integrity\"></script>");
});

test('stylesheet omits integrity for missing file', function (): void {
    $hasher = new FileHasher($this->tempDir);
    $tag    = $hasher->stylesheet('/assets/css/missing.css');

    expect($tag)->toBe('<link href="/assets/css/missing.css" rel="stylesheet">');
    expect($tag)->not->toContain('integrity');
});

test('script omits integrity for missing file', function (): void {
    $hasher = new FileHasher($this->tempDir);
    $tag    = $hasher->script('/assets/js/missing.js');

    expect($tag)->toBe('<script src="/assets/js/missing.js"></script>');
    expect($tag)->not->toContain('integrity');
});

test('stylesheetAsync generates async link without integrity by default', function (): void {
    $content = 'body { color: red; }';
    file_put_contents($this->tempDir . '/style.css', $content);

    $hasher = new FileHasher($this->tempDir);
    $tag    = $hasher->stylesheetAsync('style.css');

    $hash = substr(md5($content), 0, 8);

    expect($tag)->toContain("media=\"print\"");
    expect($tag)->toContain("onload=\"this.media='all';\"");
    expect($tag)->toContain("cache=$hash");
    expect($tag)->not->toContain('integrity');
    expect($tag)->toContain('<noscript>');
});

test('stylesheetAsync includes integrity when enabled', function (): void {
    $content = 'body { color: red; }';
    file_put_contents($this->tempDir . '/style.css', $content);

    $hasher = new FileHasher($this->tempDir);
    $tag    = $hasher->stylesheetAsync('style.css', true);

    $hash      = substr(md5($content), 0, 8);
    $integrity = 'sha384-' . base64_encode(hash('sha384', $content, true));

    expect($tag)->toContain("media=\"print\"");
    expect($tag)->toContain("cache=$hash");
    expect($tag)->toContain("integrity=\"$integrity\"");
    expect($tag)->toContain('<noscript>');
});

test('stylesheetAsync omits integrity for missing file', function (): void {
    $hasher = new FileHasher($this->tempDir);
    $tag    = $hasher->stylesheetAsync('/assets/css/missing.css');

    expect($tag)->toContain("media=\"print\"");
    expect($tag)->toContain('<noscript>');
    expect($tag)->not->toContain('integrity');
    expect($tag)->not->toContain('cache=');
});
