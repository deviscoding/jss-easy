<?php

namespace DevCoding\Jss\Helper\Command;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class WriteCommand extends AbstractWriteConsole
{
  protected function configure()
  {
    $this->setName('write');

    $this
        ->addArgument('text', InputArgument::REQUIRED)
        ->addOption('format', 'f', InputOption::VALUE_REQUIRED, 'Format of Text')
        ->addOption('line', 'ln', InputOption::VALUE_NONE, 'Add New Line After Text')
        ->addOption('width', 'w', InputOption::VALUE_REQUIRED, 'Width of Text', 50)
    ;

    parent::configure();
  }

  protected function execute(InputInterface $input, OutputInterface $output)
  {
    $text   = $this->io()->getArgument('text');
    $width  = $this->io()->getOption('width');
    $ln     = $this->io()->getOption('line');
    $format = $this->io()->getOption('format');

    return $ln ? $this->writeln($text, $format, $width) : $this->write($text, $format, $width);
  }

  protected function write($text, $format = null, $width = null)
  {
    $this->io()->write($text, $format, $width);

    return self::EXIT_SUCCESS;
  }

  protected function writeln($text, $format = null, $width = null)
  {
    $this->io()->writeln($text, $format, $width);

    return self::EXIT_SUCCESS;
  }
}
