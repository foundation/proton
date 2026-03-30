<?php

use App\Proton\MarkdownConverter;

test('generates heading IDs from text', function (): void {
    $converter = new MarkdownConverter();
    $html      = $converter->convert('## Getting Started');

    expect($html)->toContain('<h2 id="getting-started">');
});

test('generates IDs for all heading levels', function (): void {
    $converter = new MarkdownConverter();
    $html      = $converter->convert("# Title\n\n## Section\n\n### Subsection");

    expect($html)->toContain('<h1 id="title">');
    expect($html)->toContain('<h2 id="section">');
    expect($html)->toContain('<h3 id="subsection">');
});

test('strips special characters from IDs', function (): void {
    $converter = new MarkdownConverter();
    $html      = $converter->convert('## Hello, World! (Test)');

    expect($html)->toContain('id="hello-world-test"');
});

test('prefixes IDs that start with a number', function (): void {
    $converter = new MarkdownConverter();
    $html      = $converter->convert('## 3 Steps to Start');

    expect($html)->toContain('id="h-3-steps-to-start"');
});

test('extracts H2 headings into TOC', function (): void {
    $converter = new MarkdownConverter();
    $converter->convert("## First Section\n\nContent\n\n## Second Section\n\nMore content");

    $toc = $converter->getToc();

    expect($toc)->toHaveCount(2);
    expect($toc[0])->toBe(['id' => 'first-section', 'text' => 'First Section', 'level' => 2]);
    expect($toc[1])->toBe(['id' => 'second-section', 'text' => 'Second Section', 'level' => 2]);
});

test('TOC only includes H2 headings', function (): void {
    $converter = new MarkdownConverter();
    $converter->convert("# Title\n\n## Section\n\n### Subsection");

    $toc = $converter->getToc();

    expect($toc)->toHaveCount(1);
    expect($toc[0]['text'])->toBe('Section');
});

test('TOC is empty when no H2 headings exist', function (): void {
    $converter = new MarkdownConverter();
    $converter->convert("# Just a Title\n\nSome paragraph text.");

    expect($converter->getToc())->toBeEmpty();
});

test('TOC strips inline HTML from heading text', function (): void {
    $converter = new MarkdownConverter();
    $converter->convert('## Section with `code`');

    $toc = $converter->getToc();

    expect($toc[0]['text'])->toBe('Section with code');
});

test('convert resets TOC between calls', function (): void {
    $converter = new MarkdownConverter();

    $converter->convert("## First");
    expect($converter->getToc())->toHaveCount(1);

    $converter->convert("## Alpha\n\n## Beta");
    expect($converter->getToc())->toHaveCount(2);
    expect($converter->getToc()[0]['text'])->toBe('Alpha');
});

test('resetToc clears the TOC', function (): void {
    $converter = new MarkdownConverter();
    $converter->convert("## Something");

    expect($converter->getToc())->toHaveCount(1);

    $converter->resetToc();

    expect($converter->getToc())->toBeEmpty();
});
