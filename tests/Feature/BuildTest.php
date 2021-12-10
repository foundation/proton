<?php

test('inspiring command', function () {
    $this->artisan('build')
         ->expectsOutput('Build Complete.')
         ->assertExitCode(0);
});
