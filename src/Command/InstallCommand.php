<?php

namespace DevCoding\Jss\Helper\Command;

use DevCoding\Command\Base\AbstractConsole;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class InstallCommand extends AbstractConsole
{
  protected $template_format = '%s() { %s %s "$*" --%s; }';
  protected $template        = '%s() { %s %s "$*"; }';

  protected function configure()
  {
    $this->setName('install')->addArgument('path', InputArgument::OPTIONAL, 'Path to install functions.', '/usr/local/sbin/functions/');
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

    foreach (AbstractWriteConsole::FORMATS as $format)
    {
      $jf  = $format;
      $jfl = $format.'ln';
      $jfb = $format.'bg';

      $func[] = sprintf($this->template_format, $jf, $bin, 'write', $format);
      $func[] = sprintf($this->template_format, $jfl, $bin, 'writeln', $format);
      $func[] = sprintf($this->template_format, $jfb, $bin, 'badge', $format);
    }

    $func[] = sprintf($this->template, 'print', $bin, 'write');
    $func[] = sprintf($this->template, 'println', $bin, 'writeln');
    $func[] = sprintf('JHELPER="%s"', $bin);

    file_put_contents($file, implode("\n", $func));
    chmod($file, 0755);
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
