<?php

namespace DevCoding\Jss\Easy\Command;

use DevCoding\Command\Base\AbstractConsole;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class PrepCommand extends AbstractConsole
{
  protected function configure()
  {
    $this
        ->setName('prep')
        ->setDescription('Creates a source file inclusion in your JSS scripts, allowing you to access various JEZ functions through bash variables.')
        ->addArgument('path', InputArgument::OPTIONAL, 'Path to install functions.', '/usr/local/sbin/functions/')
        ->addOption('width', null, InputOption::VALUE_REQUIRED, 'The default width to use with the $JEZ_MSG variable', 50)
    ;
  }

  /**
   * @param InputInterface  $input
   * @param OutputInterface $output
   *
   * @return int
   * @throws \Exception
   */
  protected function execute(InputInterface $input, OutputInterface $output)
  {
    $bin  = $this->isPhar() ? \Phar::running(false) : $this->getProjectRoot().'/bin/console';
    $path = $input->getArgument('path');

    if (!is_dir($path))
    {
      mkdir($path, 0755, true);
    }

    $file = $path.'/_JEZ.sh';
    $line = array_merge($this->getScriptAliases($bin), $this->getScriptAliasesLegacy($bin));

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

  /**
   * @param $bin
   *
   * @return string[]
   */
  protected function getScriptAliases($bin)
  {
    $line[] = sprintf('JEZ="%s"', $bin);
    $line[] = 'JPASS="${JEZ} badge --format=pass "';
    $line[] = 'JSUCCESS="${JEZ} badge --format=success "';
    $line[] = 'JFAIL="${JEZ} badge --format=fail "';
    $line[] = 'JERROR="${JEZ} badge --format=error "';
    if ($width = $this->io()->getOption('width'))
    {
      $line[] = sprintf('JMSG="${JEZ} msg --width=%s "', $width);
    }
    else
    {
      $line[] = sprintf('JMSG="%s msg "', $bin);
    }

    return $line;
  }

  /**
   * @param $bin
   *
   * @return string[]
   */
  protected function getScriptAliasesLegacy($bin)
  {
    $line[] = sprintf('JHELPER="%s"', $bin);
    $line[] = 'JH_PASS="${JEZ} badge --format=pass "';
    $line[] = 'JH_SUCCESS="${JEZ} badge --format=success "';
    $line[] = 'JH_FAIL="${JEZ} badge --format=fail "';
    $line[] = 'JH_ERROR="${JEZ} badge --format=error "';
    if ($width = $this->io()->getOption('width'))
    {
      $line[] = sprintf('JH_MSG="${JEZ} msg --width=%s "', $width);
    }
    else
    {
      $line[] = sprintf('JH_MSG="%s msg "', $bin);
    }

    return $line;
  }

  /**
   * @return string
   * @throws \Exception
   */
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
