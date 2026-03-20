<?php

namespace App\Commands;

use LaravelZero\Framework\Commands\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class HelpCommand extends Command
{
    protected $name        = 'help';
    protected $description = 'Display help for a command';

    private const array PROTON_COMMANDS = [
        'build' => 'Build all pages',
        'data'  => 'Dump the data structure used during build',
        'init'  => 'Create the folders needed to build with proton',
        'watch' => 'Watch the template folders for changes and rebuild',
    ];

    protected function configure(): void
    {
        $this->addArgument('command_name', InputArgument::OPTIONAL, 'The command name', 'help');
    }

    #[\Override]
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $commandName = $input->getArgument('command_name');

        // If asking for help on a specific command, delegate to Symfony's helper
        if ($commandName !== 'help') {
            $command = $this->getApplication()->find($commandName);
            $helper  = new \Symfony\Component\Console\Helper\DescriptorHelper();
            $helper->describe($output, $command);

            return 0;
        }

        // Show our custom help with available commands
        $output->writeln('');
        $output->writeln('<comment>Available Commands:</comment>');
        foreach (self::PROTON_COMMANDS as $name => $desc) {
            $output->writeln(sprintf('  <info>%-12s</info> %s', $name, $desc));
        }
        $output->writeln('');
        $output->writeln('<comment>Usage:</comment>');
        $output->writeln('  proton <command> [options] [arguments]');
        $output->writeln('');
        $output->writeln('  Run <info>proton help <command></info> for more information on a specific command.');
        $output->writeln('');

        return 0;
    }
}
