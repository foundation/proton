<?php

use App\Proton\ConsoleOutput;
use App\Proton\NullOutput;
use App\Proton\Output;

test('ConsoleOutput implements Output interface', function (): void {
    $command = Mockery::mock(LaravelZero\Framework\Commands\Command::class);
    $output  = new ConsoleOutput($command);

    expect($output)->toBeInstanceOf(Output::class);
});

test('ConsoleOutput delegates to command info', function (): void {
    $command = Mockery::mock(LaravelZero\Framework\Commands\Command::class);
    $command->shouldReceive('info')->once()->with('test message');

    $output = new ConsoleOutput($command);
    $output->info('test message');
});

test('NullOutput implements Output interface', function (): void {
    $output = new NullOutput();

    expect($output)->toBeInstanceOf(Output::class);
});

test('NullOutput silently accepts messages', function (): void {
    $output = new NullOutput();
    $output->info('this should not error');

    expect(true)->toBeTrue();
});
