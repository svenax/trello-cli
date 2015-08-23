<?php

namespace Svenax\Trello\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class CommandBase extends Command
{
    public function run(InputInterface $input, OutputInterface $output)
    {
        try {
            return parent::run($input, $output);
        } catch (\Trello\Exception\RuntimeException $e) {
            if ($e->getMessage() === 'invalid token') {
                throw new \Svenax\Trello\Exception\AuthException(<<<TEXT
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
