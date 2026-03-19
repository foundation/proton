<?php

use Tests\Helpers\TestFixtures;

uses(TestFixtures::class);

beforeEach(function () {
    $this->setUpTempProject();
    $this->createDataFile('data.yml', ['title' => 'Test']);
    $this->createPage('index.html', '<h1>Home</h1>', ['layout' => 'none']);
});

afterEach(function () {
    $this->tearDownTempProject();
});

test('build command completes successfully', function () {
    $this->artisan('build')
         ->expectsOutputToContain('Build Complete')
         ->assertExitCode(0);
});
