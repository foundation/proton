<?php

arch('Proton exceptions extend RuntimeException')
    ->expect('App\Proton\Exceptions')
    ->toExtend(RuntimeException::class);

arch('Output implementations implement Output interface')
    ->expect(App\Proton\ConsoleOutput::class)
    ->toImplement(App\Proton\Output::class);

arch('NullOutput implements Output interface')
    ->expect(App\Proton\NullOutput::class)
    ->toImplement(App\Proton\Output::class);

arch('Settings DTOs are not extended')
    ->expect('App\Proton\Settings')
    ->toBeFinal();

arch('Proton classes do not use echo')
    ->expect('App\Proton')
    ->not->toUse(['echo']);

arch('Commands extend Laravel Command')
    ->expect('App\Commands')
    ->toExtend(LaravelZero\Framework\Commands\Command::class);

arch('Only Commands and ConsoleOutput depend on Command class')
    ->expect(LaravelZero\Framework\Commands\Command::class)
    ->toOnlyBeUsedIn([
        'App\Commands',
        App\Proton\ConsoleOutput::class,
    ]);

arch('Proton classes use custom exceptions not raw Exception')
    ->expect('App\Proton')
    ->not->toUse(['Exception']);
