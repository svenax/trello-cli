<?php

namespace Svenax\Trello\Command;

use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputDefinition;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Svenax\Trello\Auth;

/**
 * Symfony2 Console command to manipulate Trello boards.
 */
class BoardsCommand extends CommandBase
{
    // Command set up ----------------------------------------------------------

    /**
     * Set up the command definitions and help text for this command.
     */
    protected function configure()
    {
        $this
            ->setName('boards')
            ->setDefinition($this->createDefinition())
            ->setDescription('List all boards')
            ->setHelp(<<<TEXT
The <info>%command.name%</info> command lists all open boards:

  <info>%command.full_name% -c</info> shows also closed boards.
  <info>%command.full_name% -p</info> shows only private boards.
TEXT
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
        $private = $input->getOption('private');
        $closed = $input->getOption('closed');

        $client = Auth::getClient();
        $manager = Auth::getManager();

        $table = new Table($output);
        $table->setStyle('compact');
        $table->setHeaders(['Board', 'Cards', 'Flags']);

        foreach ($client->member()->boards()->all('me') as $board) {
            if (!$closed && $board['closed']) {
                continue;
            }
            if ($private && $board['prefs']['permissionLevel'] !== 'private') {
                continue;
            }
            $background = $board['prefs']['background'];
            $cardCount = count($client->board($board['id'])->cards()->filter($board['id'], $closed ? 'all' : 'open'));
            $flags = [
                $board['closed'] ? 'Closed' : false,
                $board['prefs']['permissionLevel'] === 'private' ? 'Private' : false,
                $board['starred'] ? 'Starred' : false,
                $board['pinned'] ? 'Pinned' : false,
            ];
            $table->addRow([
                $this->colorTag($board['name'], $background),
                sprintf('%5d', $cardCount),
                implode(', ', array_filter($flags)),
            ]);
        }

        $table->render();

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
            new InputOption('closed', 'c', InputOption::VALUE_NONE, 'Show also closed boards'),
            new InputOption('private', 'p', InputOption::VALUE_NONE, 'Show only private boards'),
        ]);
    }

    // Internal --------------------------------------------------------------

    private function colorTag($str, $trelloColor)
    {
        static $map = [
            'blue' => 'fg=blue',
            'green' => 'fg=green',
            'pink' => 'fg=yellow',
            'red' => 'fg=red',
        ];

        if (isset($map[$trelloColor])) {
            return "<{$map[$trelloColor]}>{$str}</>";
        }

        return $str;
    }
}
