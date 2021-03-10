<?php

namespace DevCoding\Jss\Helper\Command\Write;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class BadgeCommand extends AbstractWriteConsole
{
  protected function configure()
  {
    $this->setName('badge')
         ->addArgument('text', InputArgument::OPTIONAL)
         ->addOption('format', 'f', InputOption::VALUE_REQUIRED, 'Format of Text')
         ->addOption('skip-newline', 's', InputOption::VALUE_NONE)
    ;

    parent::configure();
  }

  protected function interact(InputInterface $input, OutputInterface $output)
  {
    parent::interact($input, $output);

    if (!$text = $input->getArgument('text'))
    {
      if ($input->getOption('pass'))
      {
        $text = 'PASS';
      }
      elseif ($input->getOption('fail'))
      {
        $text = 'FAIL';
      }
      elseif ($format = $input->getOption('format'))
      {
        $text = strtoupper($format);
      }

      if (!empty($text))
      {
        $input->setArgument('text', $text);
      }
    }
  }

  protected function execute(InputInterface $input, OutputInterface $output)
  {
    $format = $input->getOption('format');
    $skip   = $input->getOption('skip-newline');
    $text   = strtoupper($input->getArgument('text'));

    $this->io()->write('[');
    $this->io()->write($text, $format);

    if ($skip)
    {
      $this->io()->write(']');
    }
    else
    {
      $this->io()->writeln(']');
    }

    return self::EXIT_SUCCESS;
  }
}
