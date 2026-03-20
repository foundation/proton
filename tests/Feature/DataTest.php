<?php

use Tests\Helpers\TestFixtures;

uses(TestFixtures::class);

beforeEach(function (): void {
    $this->setUpTempProject();
    $this->createDataFile('data.yml', ['title' => 'My Site']);
});

afterEach(function (): void {
    $this->tearDownTempProject();
});

test('data command dumps global data', function (): void {
    $this->artisan('data')
         ->expectsOutputToContain('Loading global data')
         ->assertExitCode(0);
});

test('data command dumps page data', function (): void {
    $this->createPage('index.html', '<h1>Hello</h1>', ['heading' => 'Welcome']);

    $this->artisan('data', ['--page' => 'index.html'])
         ->expectsOutputToContain('Loading data for index.html')
         ->assertExitCode(0);
});
