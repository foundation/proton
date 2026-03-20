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

test('build command completes successfully', function (): void {
    $this->artisan('build')
         ->expectsOutputToContain('Build Complete')
         ->assertExitCode(0);
});
