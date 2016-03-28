<?php

namespace Svenax\Trello\Command;

use Svenax\Trello\Exception\AuthException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Trello\Exception\RuntimeException;

/**
 * Base class for our Symfony2 Console commands. Checks that we have a working
 * authentication token.
 */
class CommandBase extends Command
{
    public function run(InputInterface $input, OutputInterface $output)
    {
        try {
            return parent::run($input, $output);
        } catch (RuntimeException $e) {
            if ($e->getMessage() === 'invalid token') {
                throw new AuthException(<<<TEXT
Your authentication token is invalid or has not been set yet.
Execute 'trello config generate' and follow the instructions.
TEXT
                );
            } else {
                throw $e;
            }
        }
    }
}
