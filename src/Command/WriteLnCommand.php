<?php

namespace DevCoding\Jss\Helper\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class WriteLnCommand extends WriteCommand
{
  protected function configure()
  {
    parent::configure();

    $this->setName('writeln');
  }

  protected function interact(InputInterface $input, OutputInterface $output)
  {
    $input->setOption('line', true);

    parent::interact($input, $output);
  }
}
