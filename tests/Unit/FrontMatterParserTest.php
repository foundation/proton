<?php

use App\Proton\FrontMatterParser;

test('parseWithContent extracts front matter and content', function (): void {
    $raw = "---\ntitle: Hello\n---\n# Content here";

    $result = FrontMatterParser::parseWithContent($raw);

    expect($result['data'])->toBe(['title' => 'Hello']);
    expect($result['content'])->toBe('# Content here');
});

test('parseWithContent returns empty data when no front matter', function (): void {
    $raw = '# Just content, no front matter';

    $result = FrontMatterParser::parseWithContent($raw);

    expect($result['data'])->toBe([]);
    expect($result['content'])->toBe($raw);
});

test('parseWithContent handles empty front matter block', function (): void {
    $raw = "---\n\n---\n# Content";

    $result = FrontMatterParser::parseWithContent($raw);

    expect($result['data'])->toBe([]);
    expect($result['content'])->toBe('# Content');
});

test('parseWithContent handles multiple front matter keys', function (): void {
    $raw = "---\ntitle: Page\nlayout: custom.html\ndraft: true\n---\nBody text";

    $result = FrontMatterParser::parseWithContent($raw);

    expect($result['data'])->toBe(['title' => 'Page', 'layout' => 'custom.html', 'draft' => true]);
    expect($result['content'])->toBe('Body text');
});

test('parseDataOnly extracts front matter data', function (): void {
    $raw = "---\ntitle: Hello\nnav_order: 1\n---\n# Content here";

    $data = FrontMatterParser::parseDataOnly($raw);

    expect($data)->toBe(['title' => 'Hello', 'nav_order' => 1]);
});

test('parseDataOnly returns empty array when no front matter', function (): void {
    $raw = '# Just content';

    $data = FrontMatterParser::parseDataOnly($raw);

    expect($data)->toBe([]);
});

test('parseDataOnly returns empty array for empty front matter', function (): void {
    $raw = "---\n\n---\n# Content";

    $data = FrontMatterParser::parseDataOnly($raw);

    expect($data)->toBe([]);
});
