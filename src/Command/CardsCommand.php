<?php

namespace Svenax\Trello\Command;

use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputDefinition;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Formatter\OutputFormatterStyle;
use Symfony\Component\Console\Output\OutputInterface;
use Svenax\Trello\Auth;

/**
 * Symfony2 Console command to manipulate Trello cards.
 */
class CardsCommand extends CommandBase
{
    // Command set up ----------------------------------------------------------

    /**
     * Set up the command definitions and help text for this command.
     *
     * @throws \Symfony\Component\Console\Exception\InvalidArgumentException
     */
    protected function configure()
    {
        $this
                ->setName('cards')
                ->setDefinition($this->createDefinition())
                ->setDescription('List all cards')
                ->setHelp(<<<TEXT
The <info>%command.name%</info> command lists all open cards:

  <info>%command.full_name% -c</info> shows also closed cards.
TEXT
                );
    }

    /**
     * Execute this command according to given switches and parameters.
     *
     * @param InputInterface  $input
     * @param OutputInterface $output
     * @return int
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $index = $input->getArgument('index');
        $closed = $input->getOption('closed');

        $client = Auth::getClient();

        if ($index === null) {
            $table = new Table($output);
            $table->setStyle('compact');
            $table->setHeaders(['', 'Cards', 'List', 'Board']);

            $i = 1;
            foreach ($client->member()->cards()->all('me') as $card) {
                if (!$closed && $card['closed']) {
                    continue;
                }
                $board = $client->boards()->show($card['idBoard']);
                $list = $client->lists()->show($card['idList']);
                $background = $board['prefs']['background'];
                $table->addRow([
                        sprintf('%2d', $i++),
                        mb_substr($card['name'], 0, 45, 'UTF-8'),
                        $list['name'],
                        $this->colorTag(mb_substr($board['name'], 0, 20, 'UTF-8'), $background),
                ]);
            }

            $table->render();
        } else {
            $i = 1;
            foreach ($client->member()->cards()->all('me') as $card) {
                if (!$closed && $card['closed']) {
                    continue;
                }
                if ($i++ === (int)$index) {
                    break;
                }
            }

            $checklists = array_map(function ($id) use ($client) {
                return $client->checklists()->show($id);
            }, $card['idChecklists']);

            $this->addOutputStyles($output);
            $text = $card['desc'];
            $output->writeln("<info>{$card['name']}</>");
            $output->writeln($this->formatText($text));
            $output->writeln($this->formatChecklists($checklists));
        }

        return 0;
    }

    /**
     * Create the input definition for this command.
     *
     * @return InputDefinition
     * @throws \Symfony\Component\Console\Exception\InvalidArgumentException
     */
    protected function createDefinition()
    {
        return new InputDefinition([
                new InputArgument('index', InputArgument::OPTIONAL, 'Card index for more info'),
                new InputOption('closed', 'c', InputOption::VALUE_NONE, 'Show also closed cards'),
        ]);
    }

    // Internal --------------------------------------------------------------

    /**
     * Map from Trello board colors to available ANSI colors.
     *
     * @param string $str         Text to colorize.
     * @param string $trelloColor Trello color name.
     * @return string  Colorized text.
     */
    private function colorTag($str, $trelloColor)
    {
        static $map = [
                'blue' => 'fg=blue',
                'green' => 'fg=green',
                'pink' => 'fg=yellow',
                'red' => 'fg=red',
                'grey' => 'fg=grey',
        ];

        return isset($map[$trelloColor]) ? "<{$map[$trelloColor]}>{$str}</>" : $str;
    }

    private function addOutputStyles(OutputInterface $output)
    {
        $output->getFormatter()->setStyle('bold', new OutputFormatterStyle(null, null, ['bold']));
        $output->getFormatter()->setStyle('emph', new OutputFormatterStyle(null, null, ['underscore']));
        $output->getFormatter()->setStyle('strike', new OutputFormatterStyle(null, null, ['blink']));
    }

    /**
     * Trivial markdown -> ansi color text conversion so card content looks
     * a little nicer.
     *
     * @todo Add smart line-wrapping only for paragraph text.
     *
     * @param  string $text Markdown text.
     * @return string  Formatted text.
     */
    private function formatText($text)
    {
        $text = trim($text);
        $text = preg_replace('/\*\*(.+?)\*\*/', '<bold>$1</>', $text);
        $text = preg_replace('/\*(.+?)\*/', '<emph>$1</>', $text);
        $text = preg_replace('/~~(.+?)~~/', '<strike>$1</>', $text);
        $text = preg_replace('/^>\w*(.*)/', '    $1', $text);
        $text = preg_replace_callback('/```.*?\n(.*)```/ms', function ($m) {
            return '    ' . str_replace("\n", "\n    ", $m[1]);
        }, $text);

        return $text === '' ? '' : $text . "\n";
    }

    private function formatChecklists(array $lists)
    {
        $out = '';
        foreach ($lists as $list) {
            $total = $checked = 0;
            $outItems = '';
            foreach ($list['checkItems'] as $item) {
                $total++;
                $checkmark = ' ';
                if ($item['state'] === 'complete') {
                    $checked++;
                    $checkmark = '✔';
                }
                $outItems .= "{$checkmark} {$item['name']}\n";
            }
            $out .= "<bold>{$list['name']}:</bold> [{$checked}/{$total}]\n{$outItems}";
        }

        return $out;
    }
}
