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
    $output->detail('this should not error either');

    expect(true)->toBeTrue();
});

test('ConsoleOutput quiet mode suppresses info', function (): void {
    $command = Mockery::mock(LaravelZero\Framework\Commands\Command::class);
    $command->shouldNotReceive('info');

    $output = new ConsoleOutput($command, quiet: true);
    $output->info('should be suppressed');
});

test('ConsoleOutput verbose mode shows detail', function (): void {
    $command = Mockery::mock(LaravelZero\Framework\Commands\Command::class);
    $command->shouldReceive('line')->once()->with('  detail message');

    $output = new ConsoleOutput($command, verbose: true);
    $output->detail('detail message');
});

test('ConsoleOutput non-verbose mode hides detail', function (): void {
    $command = Mockery::mock(LaravelZero\Framework\Commands\Command::class);
    $command->shouldNotReceive('line');

    $output = new ConsoleOutput($command, verbose: false);
    $output->detail('should be hidden');
});
