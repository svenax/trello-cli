<?php

namespace Svenax\Trello\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputDefinition;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Svenax\Trello\Auth;
use Svenax\Trello\Settings;

/**
 * Symfony2 Console command manipulate config options.
 */
class ConfigCommand extends CommandBase
{
    // Command set up ----------------------------------------------------------

    /**
     * Set up the command definitions and help text for this command.
     */
    protected function configure()
    {
        $this
            ->setName('config')
            ->setDefinition($this->createDefinition())
            ->setDescription('Handle configuration options')
            ->setHelp(<<<EOT
The <info>%command.name%</info> command manipulates configuration options:

  <info>%command.full_name% get [option]</info> reads an option value.
  <info>%command.full_name% set [option] [value]</info> writes an option value.
  <info>%command.full_name% generate</info> generates a new user token.
EOT
            );
    }

    /**
     * Execute this command according to given switches and parameters.
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     *
     * @return int
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $cmd = $input->getArgument('cmd');
        switch ($cmd) {
        case 'get':
            $output->writeln(Settings::get($input->getArgument('option')));
            break;
        case 'set':
            Settings::set($input->getArgument('option'), $input->getArgument('value'));
            break;
        case 'generate';
            $url = 'https://trello.com/1/authorize?key='.Auth::APP_KEY;
            $url .= '&name=trello+cli&expiration=never&response_type=token&scope=read,write';
            $output->writeln('Paste this url into a browser when you are logged in to Trello, and then');
            $output->writeln('call <info>trello set user_token</info> with the token shown on the result page.');
            $output->writeln(sprintf("\n  <info>{$url}</info>"));
            break;
        default:
            throw new \InvalidArgumentException("Unknown command '{$cmd}'");
            break;
        }

        return 0;
    }

    /**
     * Create the input definition for this command.
     *
     * @return InputDefinition
     */
    protected function createDefinition()
    {
        return new InputDefinition([
            // WTF? The name `command` can't be used here
            new InputArgument('cmd', InputArgument::REQUIRED, 'Command'),
            new InputArgument('option', InputArgument::OPTIONAL, 'Option'),
            new InputArgument('value', InputArgument::OPTIONAL, 'Value'),
        ]);
    }
}
