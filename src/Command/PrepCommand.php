<?php

namespace DevCoding\Jss\Helper\Command;

use DevCoding\Command\Base\AbstractConsole;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\Output;
use Symfony\Component\Console\Output\OutputInterface;

class PrepCommand extends AbstractConsole
{
  protected function configure()
  {
    $this
        ->setName('prep')
        ->setDescription('Creates a source file inclusion in your JSS scripts, allowing you to access various JHelper functions through bash variables.')
        ->addArgument('path', InputArgument::OPTIONAL, 'Path to install functions.', '/usr/local/sbin/functions/')
        ->addOption('width', null, InputOption::VALUE_REQUIRED, 'The default width to use with the $JH_MSG variable', 50)
    ;
  }

  protected function execute(InputInterface $input, OutputInterface $output)
  {
    $bin  = $this->isPhar() ? \Phar::running(false) : $this->getProjectRoot().'/bin/console';
    $path = $input->getArgument('path');

    if (!is_dir($path))
    {
      mkdir($path, 0755, true);
    }

    $file = $path.'/_jhelper.sh';

    $line[] = sprintf('JHELPER="%s"', $bin);
    $line[] = sprintf('JH_PASS="${JHELPER} badge --format=pass "');
    $line[] = sprintf('JH_SUCCESS="${JHELPER} badge --format=success "');
    $line[] = sprintf('JH_FAIL="${JHELPER} badge --format=fail "');
    $line[] = sprintf('JH_ERROR="${JHELPER} badge --format=error "');
    if ($width = $this->io()->getOption('width'))
    {
      $line[] = sprintf('JH_MSG="${JHELPER} msg --width=%s "', $width);
    }
    else
    {
      $line[] = sprintf('JH_MSG="%s msg "', $bin);
    }

    if (!file_put_contents($file, implode("\n", $line)))
    {
      return self::EXIT_ERROR;
    }
    elseif (!chmod($file, 0755))
    {
      return self::EXIT_ERROR;
    }

    $this->io()->write($file, null, null, OutputInterface::VERBOSITY_QUIET);

    return self::EXIT_SUCCESS;
  }

  private function getProjectRoot()
  {
    if ($phar = \Phar::running(true))
    {
      return $phar;
    }
    else
    {
      $dir = __DIR__;
      while (!file_exists($dir.'/composer.json'))
      {
        if ($dir === dirname($dir))
        {
          throw new \Exception('The project directory could not be determined.  You must have a "composer.json" file in the project root!');
        }

        $dir = dirname($dir);
      }

      return $dir;
    }
  }
}
